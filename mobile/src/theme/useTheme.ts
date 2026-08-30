import { useColorScheme } from '@/components/useColorScheme';
import { palette, type ThemeColors } from './colors';

export function useTheme(): ThemeColors {
  const scheme = useColorScheme();
  return palette[scheme ?? 'light'];
}
