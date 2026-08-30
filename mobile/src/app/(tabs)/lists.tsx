import { useRouter } from 'expo-router';
import { FlatList, Pressable, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ChevronRightIcon, PlusIcon } from '@/components/icons';
import { useCategories } from '@/hooks/useCategories';
import type { Category } from '@/data/models';
import { useTheme } from '@/theme/useTheme';
import { getListColor } from '@/theme/listColor';

const VISIBILITY_LABEL: Record<Category['visibility'], string> = {
  private: '非公開',
  unlisted: '限定公開',
  public: '公開',
};

export default function MyListsScreen() {
  const theme = useTheme();
  const router = useRouter();
  const { categories } = useCategories();

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.bg }]} edges={['bottom']}>
      <FlatList
        data={categories}
        keyExtractor={(category) => category.id}
        ListHeaderComponent={<Text style={[styles.sectionTitle, { color: theme.textSecondary }]}>作成したリスト</Text>}
        renderItem={({ item: category }) => {
          const color = getListColor(category.id);
          return (
            <Pressable
              onPress={() => router.push(`/lists/${category.id}`)}
              style={[styles.row, { backgroundColor: theme.cardBg, borderColor: theme.border }]}>
              <View style={[styles.dot, { backgroundColor: color }]} />
              <Text style={[styles.rowTitle, { color: theme.text }]}>{category.name}</Text>
              <View style={[styles.badge, { borderColor: theme.border }]}>
                <Text style={[styles.badgeText, { color: theme.textSecondary }]}>{VISIBILITY_LABEL[category.visibility]}</Text>
              </View>
              <ChevronRightIcon color={theme.textSecondary} />
            </Pressable>
          );
        }}
        ListFooterComponent={
          <Pressable onPress={() => router.push('/lists/new')} style={[styles.newRow, { borderColor: theme.border }]}>
            <PlusIcon color={theme.accent} />
            <Text style={[styles.newRowText, { color: theme.accent }]}>新しいリストを作成</Text>
          </Pressable>
        }
        contentContainerStyle={styles.listContent}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  listContent: { paddingHorizontal: 16, paddingTop: 8, paddingBottom: 24 },
  sectionTitle: { fontSize: 13, fontWeight: '600', marginBottom: 8, marginTop: 8 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 14,
    borderWidth: StyleSheet.hairlineWidth,
    paddingVertical: 14,
    paddingHorizontal: 12,
    marginBottom: 8,
    gap: 10,
  },
  dot: { width: 10, height: 10, borderRadius: 5 },
  rowTitle: { flex: 1, fontSize: 16, fontWeight: '600' },
  badge: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, paddingHorizontal: 8, paddingVertical: 3 },
  badgeText: { fontSize: 12 },
  newRow: {
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
  newRowText: { fontSize: 15, fontWeight: '600' },
});
