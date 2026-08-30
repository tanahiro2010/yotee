import { StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { SearchTabIcon } from '@/components/icons';
import { useTheme } from '@/theme/useTheme';

/**
 * Phase 1 placeholder (ROADMAP: "この段階では「探す」はダミーでよい") — real
 * search against `GET /categories/search` arrives in Phase 3 once there's a
 * backend and a public catalog to search at all.
 */
export default function DiscoverScreen() {
  const theme = useTheme();

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.bg }]} edges={['bottom']}>
      <View style={styles.content}>
        <SearchTabIcon size={40} color={theme.textSecondary} />
        <Text style={[styles.title, { color: theme.text }]}>他の人のリストを探す</Text>
        <Text style={[styles.body, { color: theme.textSecondary }]}>この機能は近日公開予定です</Text>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 32, gap: 8 },
  title: { fontSize: 17, fontWeight: '600', marginTop: 8 },
  body: { fontSize: 14 },
});
