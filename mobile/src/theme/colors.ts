/**
 * Ported 1:1 from the approved design system in
 * examples/decided/home-timeline.html (and siblings) — see the "Yotee UI
 * decisions" project memory. Keep these in sync if that design changes;
 * don't invent new tokens ad hoc per screen.
 */
export interface ThemeColors {
  bg: string;
  pageText: string;
  cardBg: string;
  text: string;
  textSecondary: string;
  accent: string;
  accentSoft: string;
  border: string;
  tabbarBg: string;
  danger: string;
}

export const palette: { light: ThemeColors; dark: ThemeColors } = {
  light: {
    bg: '#f2f2f7',
    pageText: '#1c1c1e',
    cardBg: '#ffffff',
    text: '#1c1c1e',
    textSecondary: '#8e8e93',
    accent: '#ff6b4a',
    accentSoft: '#fff1ec',
    border: 'rgba(0,0,0,0.07)',
    tabbarBg: 'rgba(255,255,255,0.85)',
    danger: '#ff3b30',
  },
  dark: {
    bg: '#000000',
    pageText: '#f5f5f7',
    cardBg: '#1c1c1e',
    text: '#f5f5f7',
    textSecondary: '#9a9a9e',
    accent: '#ff8a68',
    accentSoft: 'rgba(255,138,104,0.14)',
    border: 'rgba(255,255,255,0.09)',
    tabbarBg: 'rgba(28,28,30,0.85)',
    danger: '#ff453a',
  },
};

/** Fixed per-List identity colors, reused everywhere a List's icon badge appears — never re-derive a color from a List's name/id. */
export const listDotColors = {
  green: '#34c759',
  purple: '#af52de',
  blue: '#0a84ff',
} as const;
