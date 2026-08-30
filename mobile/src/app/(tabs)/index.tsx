import { useRouter } from 'expo-router';
import { useMemo } from 'react';
import { Pressable, SectionList, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { CalendarDotIcon, ChevronRightIcon } from '@/components/icons';
import { useUpcomingItems } from '@/hooks/useUpcomingItems';
import type { UpcomingEntry } from '@/services/upcomingItems';
import { useTheme } from '@/theme/useTheme';
import { getListColor } from '@/theme/listColor';
import { countdownLabel, dateHeaderLabel, dayGroupKey } from '@/utils/dateLabels';

interface Section {
  title: string;
  data: UpcomingEntry[];
}

function groupByDay(entries: UpcomingEntry[]): Section[] {
  const sections: Section[] = [];
  let currentKey: string | null = null;

  for (const entry of entries) {
    const key = dayGroupKey(entry.occurrenceAt);
    if (key !== currentKey) {
      sections.push({ title: dateHeaderLabel(entry.occurrenceAt), data: [] });
      currentKey = key;
    }
    sections[sections.length - 1].data.push(entry);
  }

  return sections;
}

export default function HomeScreen() {
  const theme = useTheme();
  const router = useRouter();
  const { entries, loading } = useUpcomingItems();

  const sections = useMemo(() => groupByDay(entries), [entries]);
  const nearestOccurrenceAt = entries[0]?.occurrenceAt.getTime();

  if (!loading && entries.length === 0) {
    return (
      <SafeAreaView style={[styles.container, { backgroundColor: theme.bg }]} edges={['bottom']}>
        <View style={styles.emptyState}>
          <Text style={[styles.emptyTitle, { color: theme.text }]}>まだ予定がありません</Text>
          <Text style={[styles.emptyBody, { color: theme.textSecondary }]}>「マイリスト」からリストを作って予定を追加しましょう</Text>
        </View>
      </SafeAreaView>
    );
  }

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.bg }]} edges={['bottom']}>
      <SectionList
        sections={sections}
        keyExtractor={(entry) => `${entry.item.id}:${entry.occurrenceAt.toISOString()}`}
        renderSectionHeader={({ section }) => <Text style={[styles.sectionHeader, { color: theme.textSecondary }]}>{section.title}</Text>}
        renderItem={({ item: entry }) => {
          const isNearest = entry.occurrenceAt.getTime() === nearestOccurrenceAt;
          const listColor = getListColor(entry.category.id);

          return (
            <Pressable
              onPress={() => router.push(`/lists/${entry.category.id}`)}
              style={[
                styles.row,
                { backgroundColor: isNearest ? theme.accentSoft : theme.cardBg, borderColor: theme.border },
              ]}>
              <View style={[styles.iconBadge, { backgroundColor: `${listColor}22` }]}>
                <CalendarDotIcon color={listColor} />
              </View>
              <View style={styles.rowText}>
                <Text style={[styles.title, { color: theme.text }]}>{entry.item.name}</Text>
                <Text style={[styles.subtitle, { color: theme.textSecondary }]}>{entry.category.name}</Text>
              </View>
              {isNearest && (
                <View style={[styles.countPill, { backgroundColor: theme.accent }]}>
                  <Text style={styles.countPillText}>{countdownLabel(entry.occurrenceAt)}</Text>
                </View>
              )}
              <ChevronRightIcon color={theme.textSecondary} />
            </Pressable>
          );
        }}
        contentContainerStyle={styles.listContent}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  listContent: { paddingHorizontal: 16, paddingTop: 8, paddingBottom: 24 },
  sectionHeader: { fontSize: 13, fontWeight: '600', marginTop: 20, marginBottom: 8 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 14,
    borderWidth: StyleSheet.hairlineWidth,
    paddingVertical: 12,
    paddingHorizontal: 12,
    marginBottom: 8,
    gap: 12,
  },
  iconBadge: { width: 40, height: 40, borderRadius: 12, alignItems: 'center', justifyContent: 'center' },
  rowText: { flex: 1 },
  title: { fontSize: 16, fontWeight: '600' },
  subtitle: { fontSize: 13, marginTop: 2 },
  countPill: { borderRadius: 10, paddingHorizontal: 8, paddingVertical: 4, marginRight: 4 },
  countPillText: { color: '#fff', fontSize: 12, fontWeight: '700' },
  emptyState: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 32, gap: 8 },
  emptyTitle: { fontSize: 17, fontWeight: '600' },
  emptyBody: { fontSize: 14, textAlign: 'center' },
});
