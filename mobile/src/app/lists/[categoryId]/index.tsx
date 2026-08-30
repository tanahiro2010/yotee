import { Stack, useFocusEffect, useLocalSearchParams, useRouter } from 'expo-router';
import { useSQLiteContext } from 'expo-sqlite';
import { useCallback, useState } from 'react';
import { FlatList, Pressable, StyleSheet, Switch, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { PlusIcon } from '@/components/icons';
import { getCategory } from '@/data/categoryRepository';
import type { Category, Item } from '@/data/models';
import { listItemsForCategory, setItemNotificationsEnabled } from '@/data/itemRepository';
import { hasNotificationPermission } from '@/services/notificationPermission';
import { refreshAllNotifications } from '@/services/notificationOrchestrator';
import { useTheme } from '@/theme/useTheme';

export default function ListDetailScreen() {
  const theme = useTheme();
  const router = useRouter();
  const db = useSQLiteContext();
  const { categoryId } = useLocalSearchParams<{ categoryId: string }>();

  const [category, setCategory] = useState<Category | null>(null);
  const [items, setItems] = useState<Item[]>([]);
  const [notificationsOff, setNotificationsOff] = useState(false);

  const reload = useCallback(async () => {
    const [loadedCategory, loadedItems, permitted] = await Promise.all([
      getCategory(db, categoryId),
      listItemsForCategory(db, categoryId),
      hasNotificationPermission(),
    ]);
    setCategory(loadedCategory);
    setItems(loadedItems);
    setNotificationsOff(!permitted);
  }, [db, categoryId]);

  useFocusEffect(
    useCallback(() => {
      reload();
    }, [reload]),
  );

  async function handleToggle(item: Item, enabled: boolean) {
    // Optimistic update so the switch doesn't visually snap back while the write/refresh is in flight.
    setItems((current) => current.map((i) => (i.id === item.id ? { ...i, notificationsEnabled: enabled } : i)));
    await setItemNotificationsEnabled(db, item.id, enabled);
    await refreshAllNotifications(db);
  }

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.bg }]} edges={['bottom']}>
      <Stack.Screen options={{ title: category?.name ?? '' }} />

      {notificationsOff && (
        <View style={[styles.notice, { backgroundColor: theme.accentSoft }]}>
          <Text style={[styles.noticeText, { color: theme.text }]}>通知がオフになっています</Text>
        </View>
      )}

      <FlatList
        data={items}
        keyExtractor={(item) => item.id}
        renderItem={({ item }) => (
          <View style={[styles.row, { backgroundColor: theme.cardBg, borderColor: theme.border }]}>
            <Text style={[styles.rowTitle, { color: theme.text }]}>{item.name}</Text>
            <Switch value={item.notificationsEnabled} onValueChange={(value) => handleToggle(item, value)} />
          </View>
        )}
        ListFooterComponent={
          <Pressable
            onPress={() => router.push({ pathname: '/items/new', params: { categoryId } })}
            style={[styles.addRow, { borderColor: theme.border }]}>
            <PlusIcon color={theme.accent} />
            <Text style={[styles.addRowText, { color: theme.accent }]}>予定を追加</Text>
          </Pressable>
        }
        contentContainerStyle={styles.listContent}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  notice: { paddingVertical: 8, paddingHorizontal: 16 },
  noticeText: { fontSize: 13, textAlign: 'center' },
  listContent: { paddingHorizontal: 16, paddingTop: 12, paddingBottom: 24 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderRadius: 14,
    borderWidth: StyleSheet.hairlineWidth,
    paddingVertical: 12,
    paddingHorizontal: 14,
    marginBottom: 8,
  },
  rowTitle: { fontSize: 16, fontWeight: '600' },
  addRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    borderWidth: 1,
    borderStyle: 'dashed',
    borderRadius: 14,
    paddingVertical: 14,
    marginTop: 8,
  },
  addRowText: { fontSize: 15, fontWeight: '600' },
});
