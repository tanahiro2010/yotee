import { Tabs } from 'expo-router';

import { HomeTabIcon, ListTabIcon, SearchTabIcon } from '@/components/icons';
import { useTheme } from '@/theme/useTheme';

export default function TabLayout() {
  const theme = useTheme();

  return (
    <Tabs
      screenOptions={{
        tabBarActiveTintColor: theme.accent,
        tabBarInactiveTintColor: theme.textSecondary,
        tabBarStyle: { backgroundColor: theme.tabbarBg },
        headerStyle: { backgroundColor: theme.cardBg },
        headerTintColor: theme.text,
      }}>
      <Tabs.Screen
        name="index"
        options={{
          title: 'ホーム',
          tabBarIcon: ({ color }) => <HomeTabIcon color={String(color)} />,
        }}
      />
      <Tabs.Screen
        name="discover"
        options={{
          title: '探す',
          tabBarIcon: ({ color }) => <SearchTabIcon color={String(color)} />,
        }}
      />
      <Tabs.Screen
        name="lists"
        options={{
          title: 'マイリスト',
          tabBarIcon: ({ color }) => <ListTabIcon color={String(color)} />,
        }}
      />
    </Tabs>
  );
}
