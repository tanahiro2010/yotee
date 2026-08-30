/**
 * Ported 1:1 from the approved mockups (examples/decided/*.html) — see the
 * "Icon style feedback" project memory: no emoji anywhere in this app's UI,
 * only these stroke-based SVGs. Add new icons here as new screens need them
 * rather than reaching for an emoji or a generic icon-font glyph.
 */
import Svg, { Circle, Line, Path, Polyline, Rect } from 'react-native-svg';

interface IconProps {
  size?: number;
  color?: string;
}

const STROKE_PROPS = { fill: 'none', strokeWidth: 1.8, strokeLinecap: 'round', strokeLinejoin: 'round' } as const;

export function FireIcon({ size = 20, color = '#000' }: IconProps) {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" stroke={color} {...STROKE_PROPS}>
      <Path d="M12 3c1.2 3.1-2 4.4-2 7.4a4 4 0 1 0 8 0c0-1.1-.7-2-1-2.8 1.3 1.1 2.3 3 2.3 5.2a6.3 6.3 0 1 1-12.6 0c0-4.3 3.1-6.7 5.3-9.8z" />
    </Svg>
  );
}

export function TicketIcon({ size = 20, color = '#000' }: IconProps) {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" stroke={color} {...STROKE_PROPS}>
      <Path d="M3 8.5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1.7a2.3 2.3 0 0 0 0 3.6v1.7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1.7a2.3 2.3 0 0 0 0-3.6z" />
      <Line x1={13.5} y1={6.8} x2={13.5} y2={17.2} strokeDasharray="2.4 2.4" stroke={color} strokeWidth={1.8} />
    </Svg>
  );
}

export function GameIcon({ size = 20, color = '#000' }: IconProps) {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" stroke={color} {...STROKE_PROPS}>
      <Rect x={2.5} y={8} width={19} height={10} rx={4} />
      <Line x1={7.5} y1={11} x2={7.5} y2={15} stroke={color} strokeWidth={1.8} />
      <Line x1={5.5} y1={13} x2={9.5} y2={13} stroke={color} strokeWidth={1.8} />
      <Circle cx={15.5} cy={12} r={0.9} fill={color} stroke="none" />
      <Circle cx={18} cy={14.5} r={0.9} fill={color} stroke="none" />
    </Svg>
  );
}

export function RecycleIcon({ size = 20, color = '#000' }: IconProps) {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" stroke={color} {...STROKE_PROPS}>
      <Path d="M17 2.5l3.5 3.5-3.5 3.5" />
      <Path d="M3.5 10.5V9a4 4 0 0 1 4-4h13" />
      <Path d="M7 21.5L3.5 18l3.5-3.5" />
      <Path d="M20.5 13.5V15a4 4 0 0 1-4 4h-13" />
    </Svg>
  );
}

export function ConcertIcon({ size = 20, color = '#000' }: IconProps) {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" stroke={color} {...STROKE_PROPS}>
      <Rect x={9} y={2} width={6} height={11} rx={3} />
      <Path d="M5.5 11a6.5 6.5 0 0 0 13 0" />
      <Line x1={12} y1={17.5} x2={12} y2={21.5} stroke={color} strokeWidth={1.8} />
      <Line x1={8.5} y1={21.5} x2={15.5} y2={21.5} stroke={color} strokeWidth={1.8} />
    </Svg>
  );
}

/** Generic fallback for schedule items that don't match a themed icon above yet. */
export function CalendarDotIcon({ size = 20, color = '#000' }: IconProps) {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" stroke={color} {...STROKE_PROPS}>
      <Rect x={3} y={4} width={18} height={17} rx={3} />
      <Line x1={3} y1={9} x2={21} y2={9} stroke={color} strokeWidth={1.8} />
      <Circle cx={12} cy={14} r={1.6} fill={color} stroke="none" />
    </Svg>
  );
}

export function ChevronRightIcon({ size = 16, color = '#000' }: IconProps) {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" stroke={color} strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" fill="none">
      <Polyline points="9 5 16 12 9 19" />
    </Svg>
  );
}

export function PlusIcon({ size = 16, color = '#000' }: IconProps) {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" stroke={color} strokeWidth={2.2} strokeLinecap="round" strokeLinejoin="round" fill="none">
      <Line x1={12} y1={5} x2={12} y2={19} stroke={color} strokeWidth={2.2} />
      <Line x1={5} y1={12} x2={19} y2={12} stroke={color} strokeWidth={2.2} />
    </Svg>
  );
}

export function GearIcon({ size = 22, color = '#000' }: IconProps) {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" stroke={color} strokeWidth={1.7} strokeLinecap="round" strokeLinejoin="round" fill="none">
      <Circle cx={12} cy={12} r={3} />
      <Path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82A1.65 1.65 0 0 0 3 15.09H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.3.66.94 1.09 1.66 1.09H21a2 2 0 0 1 0 4h-.09c-.72 0-1.36.43-1.51 1z" />
    </Svg>
  );
}

export function HomeTabIcon({ size = 21, color = '#000' }: IconProps) {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" stroke={color} {...STROKE_PROPS}>
      <Path d="M3.5 10.5L12 3.5l8.5 7" />
      <Path d="M5.5 9.5V19a1.5 1.5 0 0 0 1.5 1.5h3v-6h4v6h3A1.5 1.5 0 0 0 18.5 19V9.5" />
    </Svg>
  );
}

export function SearchTabIcon({ size = 21, color = '#000' }: IconProps) {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" stroke={color} {...STROKE_PROPS}>
      <Circle cx={10.5} cy={10.5} r={6.5} />
      <Line x1={19.5} y1={19.5} x2={15.3} y2={15.3} stroke={color} strokeWidth={1.8} />
    </Svg>
  );
}

export function ListTabIcon({ size = 21, color = '#000' }: IconProps) {
  return (
    <Svg width={size} height={size} viewBox="0 0 24 24" stroke={color} {...STROKE_PROPS}>
      <Line x1={9} y1={6.5} x2={20.5} y2={6.5} stroke={color} strokeWidth={1.8} />
      <Line x1={9} y1={12} x2={20.5} y2={12} stroke={color} strokeWidth={1.8} />
      <Line x1={9} y1={17.5} x2={20.5} y2={17.5} stroke={color} strokeWidth={1.8} />
      <Line x1={4} y1={6.5} x2={4.01} y2={6.5} stroke={color} strokeWidth={1.8} />
      <Line x1={4} y1={12} x2={4.01} y2={12} stroke={color} strokeWidth={1.8} />
      <Line x1={4} y1={17.5} x2={4.01} y2={17.5} stroke={color} strokeWidth={1.8} />
    </Svg>
  );
}
