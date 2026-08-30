namespace Yotee;

enum DevicePlatform {
  ios: "ios",
  android: "android",
}

/**
 * A push-registered device (PRD §47 `devices`). Its only server-side job is
 * receiving `category.updated` sync pings — it never carries reminder
 * payloads, because the backend never executes reminders (§22, §26).
 */
model Device {
  @format("uuid")
  id: string;

  platform: DevicePlatform;

  /** Opaque APNs/FCM push token. */
  pushToken: string;

  lastSeenAt?: utcDateTime;
  createdAt: utcDateTime;
  updatedAt: utcDateTime;
}

model RegisterDeviceRequest {
  platform: DevicePlatform;
  pushToken: string;
}
