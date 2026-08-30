import DateTimePicker from '@react-native-community/datetimepicker';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useSQLiteContext } from 'expo-sqlite';
import { useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { createItem } from '@/data/itemRepository';
import { refreshAllNotifications } from '@/services/notificationOrchestrator';
import type { ReminderPreset } from '@/domain/notificationScheduler/types';
import type { ScheduleRule, Weekday } from '@/domain/scheduleEngine/types';
import { useTheme } from '@/theme/useTheme';

type UiScheduleType = 'once' | 'weekly' | 'monthly' | 'yearly';
type MonthlySubMode = 'day' | 'nth_weekday';

const UI_SCHEDULE_TYPES: { key: UiScheduleType; label: string }[] = [
  { key: 'once', label: '一度だけ' },
  { key: 'weekly', label: '毎週' },
  { key: 'monthly', label: '毎月' },
  { key: 'yearly', label: '毎年' },
];

const WEEKDAY_LABELS: { value: Weekday; label: string }[] = [
  { value: 0, label: '日' },
  { value: 1, label: '月' },
  { value: 2, label: '火' },
  { value: 3, label: '水' },
  { value: 4, label: '木' },
  { value: 5, label: '金' },
  { value: 6, label: '土' },
];

const REMINDER_PRESETS: { key: ReminderPreset; label: string }[] = [
  { key: 'night_before', label: '前日の夜' },
  { key: 'same_day_morning', label: '当日の朝' },
  { key: 'custom', label: 'カスタム' },
];

function formatTime(date: Date): string {
  return `${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}`;
}

function formatDate(date: Date): string {
  return `${date.getFullYear()}年${date.getMonth() + 1}月${date.getDate()}日`;
}

export default function NewItemScreen() {
  const theme = useTheme();
  const router = useRouter();
  const db = useSQLiteContext();
  const { categoryId } = useLocalSearchParams<{ categoryId: string }>();

  const [name, setName] = useState('');
  const [uiScheduleType, setUiScheduleType] = useState<UiScheduleType>('weekly');
  const [monthlySubMode, setMonthlySubMode] = useState<MonthlySubMode>('day');
  const [weekdays, setWeekdays] = useState<Weekday[]>([]);
  const [monthDay, setMonthDay] = useState('1');
  const [nth, setNth] = useState('1');
  const [nthWeekday, setNthWeekday] = useState<Weekday>(1);
  const [yearMonth, setYearMonth] = useState('1');
  const [yearDay, setYearDay] = useState('1');
  const [timeOfDay, setTimeOfDay] = useState(new Date(2000, 0, 1, 8, 0));
  const [onceDate, setOnceDate] = useState(new Date());
  const [showTimePicker, setShowTimePicker] = useState(false);
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [reminderPreset, setReminderPreset] = useState<ReminderPreset>('night_before');
  const [customMinutesBefore, setCustomMinutesBefore] = useState('60');
  const [saving, setSaving] = useState(false);

  function toggleWeekday(value: Weekday) {
    setWeekdays((current) => (current.includes(value) ? current.filter((w) => w !== value) : [...current, value]));
  }

  function buildScheduleRule(): ScheduleRule | null {
    const time = formatTime(timeOfDay);

    switch (uiScheduleType) {
      case 'once': {
        const at = new Date(onceDate);
        at.setHours(timeOfDay.getHours(), timeOfDay.getMinutes(), 0, 0);
        return { scheduleType: 'once', at: at.toISOString() };
      }
      case 'weekly':
        return weekdays.length > 0 ? { scheduleType: 'weekly', weekdays, time } : null;
      case 'monthly':
        if (monthlySubMode === 'day') {
          const day = Number(monthDay);
          return day >= 1 && day <= 31 ? { scheduleType: 'monthly_day', day, time } : null;
        }
        return { scheduleType: 'monthly_nth_weekday', nth: Number(nth), weekday: nthWeekday, time };
      case 'yearly': {
        const month = Number(yearMonth);
        const day = Number(yearDay);
        return month >= 1 && month <= 12 && day >= 1 && day <= 31 ? { scheduleType: 'yearly', month, day, time } : null;
      }
    }
  }

  const scheduleRule = buildScheduleRule();
  const canSave = name.trim().length > 0 && scheduleRule !== null && !saving;

  async function handleSave() {
    if (!canSave || !scheduleRule) {
      return;
    }
    setSaving(true);

    try {
      await createItem(db, {
        categoryId,
        name: name.trim(),
        scheduleRule,
        reminderRule: {
          preset: reminderPreset,
          customMinutesBefore: reminderPreset === 'custom' ? Number(customMinutesBefore) : undefined,
        },
      });

      await refreshAllNotifications(db);
      router.back();
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
        <Text style={[styles.headerTitle, { color: theme.text }]}>予定を追加</Text>
        <Pressable onPress={handleSave} disabled={!canSave}>
          <Text style={[styles.headerButton, { color: canSave ? theme.accent : theme.textSecondary }]}>保存</Text>
        </Pressable>
      </View>

      <ScrollView contentContainerStyle={styles.form}>
        <TextInput
          value={name}
          onChangeText={setName}
          placeholder="例：燃えるゴミ"
          placeholderTextColor={theme.textSecondary}
          style={[styles.input, { color: theme.text, borderColor: theme.border, backgroundColor: theme.cardBg }]}
          autoFocus
        />

        <View style={styles.segmented}>
          {UI_SCHEDULE_TYPES.map(({ key, label }) => (
            <Pressable
              key={key}
              onPress={() => setUiScheduleType(key)}
              style={[
                styles.segment,
                { borderColor: theme.border },
                uiScheduleType === key && { backgroundColor: theme.accent, borderColor: theme.accent },
              ]}>
              <Text style={[styles.segmentText, { color: uiScheduleType === key ? '#fff' : theme.text }]}>{label}</Text>
            </Pressable>
          ))}
        </View>

        {uiScheduleType === 'once' && (
          <View style={styles.card}>
            <Pressable onPress={() => setShowDatePicker(true)} style={[styles.pickerRow, { borderColor: theme.border }]}>
              <Text style={{ color: theme.text }}>{formatDate(onceDate)}</Text>
            </Pressable>
            {showDatePicker && (
              <DateTimePicker
                value={onceDate}
                mode="date"
                onChange={(_event, date) => {
                  setShowDatePicker(false);
                  if (date) setOnceDate(date);
                }}
              />
            )}
          </View>
        )}

        {uiScheduleType === 'weekly' && (
          <View style={styles.weekdays}>
            {WEEKDAY_LABELS.map(({ value, label }) => (
              <Pressable
                key={value}
                onPress={() => toggleWeekday(value)}
                style={[
                  styles.weekdayChip,
                  { borderColor: theme.border },
                  weekdays.includes(value) && { backgroundColor: theme.accent, borderColor: theme.accent },
                ]}>
                <Text style={{ color: weekdays.includes(value) ? '#fff' : theme.text }}>{label}</Text>
              </Pressable>
            ))}
          </View>
        )}

        {uiScheduleType === 'monthly' && (
          <View style={styles.card}>
            <View style={styles.segmented}>
              <Pressable
                onPress={() => setMonthlySubMode('day')}
                style={[
                  styles.segment,
                  { borderColor: theme.border },
                  monthlySubMode === 'day' && { backgroundColor: theme.accent, borderColor: theme.accent },
                ]}>
                <Text style={{ color: monthlySubMode === 'day' ? '#fff' : theme.text }}>日付で指定</Text>
              </Pressable>
              <Pressable
                onPress={() => setMonthlySubMode('nth_weekday')}
                style={[
                  styles.segment,
                  { borderColor: theme.border },
                  monthlySubMode === 'nth_weekday' && { backgroundColor: theme.accent, borderColor: theme.accent },
                ]}>
                <Text style={{ color: monthlySubMode === 'nth_weekday' ? '#fff' : theme.text }}>曜日で指定</Text>
              </Pressable>
            </View>

            {monthlySubMode === 'day' ? (
              <View style={styles.inlineRow}>
                <Text style={{ color: theme.text }}>毎月</Text>
                <TextInput
                  value={monthDay}
                  onChangeText={setMonthDay}
                  keyboardType="number-pad"
                  style={[styles.smallInput, { color: theme.text, borderColor: theme.border }]}
                />
                <Text style={{ color: theme.text }}>日</Text>
              </View>
            ) : (
              <View style={styles.inlineRow}>
                <Text style={{ color: theme.text }}>毎月第</Text>
                <TextInput
                  value={nth}
                  onChangeText={setNth}
                  keyboardType="number-pad"
                  style={[styles.smallInput, { color: theme.text, borderColor: theme.border }]}
                />
                <View style={styles.weekdays}>
                  {WEEKDAY_LABELS.map(({ value, label }) => (
                    <Pressable
                      key={value}
                      onPress={() => setNthWeekday(value)}
                      style={[
                        styles.weekdayChip,
                        { borderColor: theme.border },
                        nthWeekday === value && { backgroundColor: theme.accent, borderColor: theme.accent },
                      ]}>
                      <Text style={{ color: nthWeekday === value ? '#fff' : theme.text }}>{label}</Text>
                    </Pressable>
                  ))}
                </View>
                <Text style={{ color: theme.text }}>曜日</Text>
              </View>
            )}
          </View>
        )}

        {uiScheduleType === 'yearly' && (
          <View style={styles.inlineRow}>
            <TextInput
              value={yearMonth}
              onChangeText={setYearMonth}
              keyboardType="number-pad"
              style={[styles.smallInput, { color: theme.text, borderColor: theme.border }]}
            />
            <Text style={{ color: theme.text }}>月</Text>
            <TextInput
              value={yearDay}
              onChangeText={setYearDay}
              keyboardType="number-pad"
              style={[styles.smallInput, { color: theme.text, borderColor: theme.border }]}
            />
            <Text style={{ color: theme.text }}>日</Text>
          </View>
        )}

        <Pressable onPress={() => setShowTimePicker(true)} style={[styles.card, styles.pickerRow, { borderColor: theme.border }]}>
          <Text style={{ color: theme.textSecondary }}>時刻</Text>
          <Text style={{ color: theme.text }}>{formatTime(timeOfDay)}</Text>
        </Pressable>
        {showTimePicker && (
          <DateTimePicker
            value={timeOfDay}
            mode="time"
            onChange={(_event, date) => {
              setShowTimePicker(false);
              if (date) setTimeOfDay(date);
            }}
          />
        )}

        <Text style={[styles.label, { color: theme.textSecondary }]}>通知するタイミング</Text>
        <View style={styles.segmented}>
          {REMINDER_PRESETS.map(({ key, label }) => (
            <Pressable
              key={key}
              onPress={() => setReminderPreset(key)}
              style={[
                styles.segment,
                { borderColor: theme.border },
                reminderPreset === key && { backgroundColor: theme.accent, borderColor: theme.accent },
              ]}>
              <Text style={{ color: reminderPreset === key ? '#fff' : theme.text }}>{label}</Text>
            </Pressable>
          ))}
        </View>
        {reminderPreset === 'custom' && (
          <View style={styles.inlineRow}>
            <TextInput
              value={customMinutesBefore}
              onChangeText={setCustomMinutesBefore}
              keyboardType="number-pad"
              style={[styles.smallInput, { color: theme.text, borderColor: theme.border }]}
            />
            <Text style={{ color: theme.text }}>分前</Text>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
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
  form: { padding: 16, gap: 16 },
  input: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 12, paddingHorizontal: 14, paddingVertical: 12, fontSize: 16 },
  segmented: { flexDirection: 'row', gap: 8 },
  segment: { flex: 1, borderWidth: StyleSheet.hairlineWidth, borderRadius: 10, paddingVertical: 8, alignItems: 'center' },
  segmentText: { fontSize: 13, fontWeight: '600' },
  weekdays: { flexDirection: 'row', gap: 8, flexWrap: 'wrap' },
  weekdayChip: { width: 36, height: 36, borderRadius: 18, borderWidth: StyleSheet.hairlineWidth, alignItems: 'center', justifyContent: 'center' },
  card: { gap: 12 },
  pickerRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', borderWidth: StyleSheet.hairlineWidth, borderRadius: 12, padding: 12 },
  inlineRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  smallInput: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, paddingHorizontal: 10, paddingVertical: 6, width: 56, textAlign: 'center' },
  label: { fontSize: 13, fontWeight: '600' },
});
