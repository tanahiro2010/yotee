import { useRouter } from 'expo-router';
import { useSQLiteContext } from 'expo-sqlite';
import { useState } from 'react';
import { Alert, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { createCategory } from '@/data/categoryRepository';
import { hasNotificationPermission, requestNotificationPermission } from '@/services/notificationPermission';
import { useTheme } from '@/theme/useTheme';

/**
 * Phase 1 keeps this deliberately minimal (CLAUDE.md product principle #2
 * "入力を減らす", #3 "通知設定を難しくしない"): just a name. Visibility
 * isn't exposed at all yet — there's no backend to publish to until Phase 3,
 * so every List is `private` for now. Timezone is auto-detected, never asked.
 */
export default function NewListScreen() {
  const theme = useTheme();
  const router = useRouter();
  const db = useSQLiteContext();
  const [name, setName] = useState('');
  const [saving, setSaving] = useState(false);

  const canSave = name.trim().length > 0 && !saving;

  async function handleSave() {
    if (!canSave) {
      return;
    }
    setSaving(true);

    try {
      const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
      const category = await createCategory(db, { name: name.trim(), visibility: 'private', timezone });

      // Only ever prompted here — the first moment a List is created (PRD
      // §54) — and only if the user has never been asked before (§55: a
      // denial must not be re-prompted aggressively).
      const alreadyDecided = await hasNotificationPermission();
      if (!alreadyDecided) {
        await maybeRequestNotificationPermission();
      }

      router.replace(`/lists/${category.id}`);
    } finally {
      setSaving(false);
    }
  }

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.bg }]}>
      <View style={[styles.header, { borderBottomColor: theme.border }]}>
        <Pressable onPress={() => router.back()}>
          <Text style={[styles.headerButton, { color: theme.textSecondary }]}>キャンセル</Text>
        </Pressable>
        <Text style={[styles.headerTitle, { color: theme.text }]}>新しいリスト</Text>
        <Pressable onPress={handleSave} disabled={!canSave}>
          <Text style={[styles.headerButton, { color: canSave ? theme.accent : theme.textSecondary }]}>保存</Text>
        </Pressable>
      </View>

      <View style={styles.form}>
        <TextInput
          value={name}
          onChangeText={setName}
          placeholder="例：ゴミの日"
          placeholderTextColor={theme.textSecondary}
          style={[styles.input, { color: theme.text, borderColor: theme.border, backgroundColor: theme.cardBg }]}
          autoFocus
        />
      </View>
    </SafeAreaView>
  );
}

function maybeRequestNotificationPermission(): Promise<boolean> {
  return new Promise((resolve) => {
    Alert.alert('この予定を忘れないように', '通知をオンにしますか？', [
      { text: '後で', style: 'cancel', onPress: () => resolve(false) },
      { text: '通知をオンにする', onPress: () => requestNotificationPermission().then(resolve) },
    ]);
  });
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
  headerButton: { fontSize: 16 },
  headerTitle: { fontSize: 16, fontWeight: '600' },
  form: { padding: 16 },
  input: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 12, paddingHorizontal: 14, paddingVertical: 12, fontSize: 16 },
});
