import * as Notifications from 'expo-notifications';

/**
 * Never call `requestNotificationPermission` on app launch — only from a
 * List-create or List-subscribe flow, after showing the "この予定を忘れない
 * ように、通知をオンにしますか？" explainer (PRD §54). A denial must not
 * block using the app, and must not be re-prompted aggressively (§55) —
 * callers should check `hasNotificationPermission` and just show a passive
 * "通知がオフになっています" hint instead of calling request again.
 */
export async function hasNotificationPermission(): Promise<boolean> {
  const { status } = await Notifications.getPermissionsAsync();
  return status === 'granted';
}

export async function requestNotificationPermission(): Promise<boolean> {
  const { status } = await Notifications.requestPermissionsAsync();
  return status === 'granted';
}
