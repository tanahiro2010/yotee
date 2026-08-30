# 予定購読・通知アプリ 詳細PRD

**Version:** 0.1  
**Status:** Draft / MVP設計  
**対象プラットフォーム:** iOS / Android  
**プロダクト名:** Yotee / ヨティー

---

# 1. プロダクト概要

本プロダクトは、カレンダーではなく、

> **「未来の予定リスト」を作成・購読し、必要なタイミングで通知してもらうアプリ**

である。

ユーザーは、自分専用の予定リストを作ることも、他のユーザーが公開した予定リストを登録することもできる。

代表例：

- ゴミの日
- アイドル・アーティストのライブ
- チケット販売開始・抽選締切
- ゲーム・漫画等の発売日
- 学校行事
- 地域イベント
- カンファレンス
- 申込期限
- 定期的な生活タスク

一般的なカレンダーアプリのように「一日の予定を管理する」ことを主目的としない。

本プロダクトの価値は、

> **自分が覚えておく必要をなくすこと**

にある。

---

# 2. 一文での定義

> **自分や他人が作った「未来の予定リスト」を登録・購読すると、必要なタイミングで端末が知らせてくれるアプリ。**

将来的には、

> **予定情報そのものを公開・販売できるSchedule Marketplace**

へ発展させる。

---

# 3. 解決する課題

## 3.1 自分で予定を管理する手間

現在のカレンダーやリマインダーでは、ユーザー自身が予定を入力する必要がある。

例えばゴミの日なら、

- 燃えるゴミ
- プラスチック
- 資源ごみ
- 燃えないゴミ

を自分で調べ、すべて登録する必要がある。

本サービスでは、誰かが作成した「○○市○○地区 ゴミの日」リストを登録するだけで済む。

---

## 3.2 日付変更を自分で追従する必要がある

イベントの日程変更やチケット販売日の変更が発生した場合、従来はユーザー自身がカレンダーを書き換える必要がある。

本サービスでは作成者が予定を変更すると、購読者へ変更が同期される。

---

## 3.3 カレンダーに入れるほどではないが忘れたくない

「明日はゴミの日」

「明日チケット抽選締切」

「来週ゲーム発売」

などは、必ずしもカレンダー上で管理したい予定ではない。

本サービスは、

**予定管理より通知に重点を置く。**

---

# 4. ターゲットユーザー

メインターゲットは一般消費者。

技術者向けUI・cron式等は提供しない。

## Persona A：生活リマインダー利用者

例：

- 家庭のゴミの日を忘れたくない
- 学校や地域の定期予定を忘れたくない
- 毎週・毎月発生する予定を簡単に通知してほしい

---

## Persona B：推し活ユーザー

例：

- ライブ
- チケット抽選
- 一般販売
- グッズ販売
- 配信

を追跡したい。

---

## Persona C：情報整理クリエイター

特定ジャンルの予定を整理しているユーザー。

例：

- イベント情報まとめ
- アーティスト情報まとめ
- 地域情報
- セール情報
- 発売情報

将来的には作成したリストを販売できる。

---

# 5. UX原則

本サービスでは、内部データ構造をそのままユーザーへ見せない。

ユーザーが基本的に理解する概念は、

1. **リスト**
2. **予定**

の2つだけとする。

内部用語：

```text
Category
└── Item
    └── Schedule Rule
        └── Occurrence
            └── Notification
```

ユーザーUI：

```text
リスト
└── 予定
```

とする。

`Category`、`Item`、`Cron`、`Occurrence` 等の技術用語は通常UIには表示しない。

---

# 6. リスト / Category

複数の関連予定をまとめる単位。

例：

```text
ゴミの日
├── 燃えるゴミ
├── プラスチック
├── 資源ごみ
└── 燃えないゴミ
```

または、

```text
○○ WORLD TOUR 2027
├── FC先行開始
├── FC先行締切
├── 一般販売開始
├── 東京公演
├── 大阪公演
└── 福岡公演
```

以下の操作は原則リスト単位で行う。

- 公開
- 共有
- 購読
- 将来的な販売
- 所有権管理

---

# 7. 予定 / Item

リスト内部に存在する個別の予定。

Itemは以下の情報を持てる。

- 名前
- 説明
- スケジュール
- URL
- 場所
- 補足情報

MVPでは名前とスケジュールのみ必須。

---

# 8. Schedule Rule

予定は「日付そのもの」ではなく、必要に応じて繰り返しルールを持つ。

MVPで対応するルール：

### Once

一度だけ。

例：

```text
2026/09/30 18:00
```

### Weekly

毎週指定曜日。

例：

```text
毎週 火曜日・金曜日
```

### Monthly Day

毎月指定日。

例：

```text
毎月15日
```

### Monthly Nth Weekday

毎月第N曜日。

例：

```text
毎月 第2木曜日
```

### Yearly

毎年指定日。

例：

```text
毎年8月18日
```

---

# 9. タイムゾーン

予定にはIANA Time Zoneを保持する。

例：

```text
Asia/Tokyo
America/New_York
```

周期予定は単純なUTC周期として保存せず、

**「現地時間 + Time Zone」**

として表現する。

これによりDSTが存在する地域でも、

「毎週月曜20時」

という意味を維持する。

---

# 10. 通知

予定が発生する時刻とは別に、通知タイミングを設定する。

代表例：

```text
前日の20:00
当日の08:00
1時間前
3時間前
```

ただし初期UIでは複雑な設定を見せない。

基本UI：

```text
通知するタイミング

● 前日の夜
○ 当日の朝
○ カスタム
```

---

# 11. 作成者と購読者の通知設定

予定作成者は「おすすめ通知タイミング」を指定できる。

例：

```text
燃えるゴミ
おすすめ：前日の20時
```

ただし最終的な通知設定は購読者が決定する。

購読時にはおすすめ設定を初期値としてコピーする。

以降のユーザー固有通知設定は端末内に保存する。

これによりサーバーはユーザーごとの細かな通知時刻を管理しない。

---

# 12. 通知ON/OFF

リスト登録時は原則としてすべてのItemを通知対象とする。

ユーザーは後から、

```text
通知する予定

☑ 燃えるゴミ
☑ プラスチック
☐ 燃えないゴミ
☑ 資源ごみ
```

のように個別変更できる。

---

# 13. ホーム画面

カレンダーUIをメインにしない。

ホームでは、近い未来の予定を時系列表示する。

例：

```text
今日
────────────────

明日

🔥 燃えるゴミ
ゴミの日


9月3日

🎫 チケット先行締切
○○ WORLD TOUR 2027


9月8日

🎮 ○○発売日
ゲーム発売予定
```

目的は、

> **「次に何があるか」を一瞬で理解できること**

である。

---

# 14. メインナビゲーション

MVPでは3タブ構成とする。

```text
ホーム ｜ 探す ｜ マイリスト
```

## ホーム

自分が登録している予定のタイムライン。

## 探す

公開リストを検索・発見する。

## マイリスト

- 自分が作ったリスト
- 登録しているリスト

を管理する。

---

# 15. リスト作成UX

作成開始時には高度な設定を表示しない。

```text
新しいリスト

名前
[ ゴミの日 ]

＋ 予定を追加

[ 保存 ]
```

作成後、

```text
…
├ 公開する
├ 共有する
└ 削除する
```

から追加機能へアクセスする。

将来的に有料販売を追加した場合も、

```text
…
└ 販売する
```

として段階的に公開する。

---

# 16. 予定作成UX

例：

```text
予定を追加

名前
[ 燃えるゴミ ]

いつ？
[ 毎週 ]

曜日
[ 火 ] [ 金 ]

通知
[ 前日の夜 ]

[ 保存 ]
```

一般ユーザーにcron式等を入力させない。

---

# 17. 公開設定

Categoryは以下の公開状態を持つ。

```text
private
unlisted
public
```

### private

所有者のみ閲覧可能。

### unlisted

検索結果には表示されないが、共有URLを知っているユーザーは閲覧可能。

### public

検索結果に表示される。

---

# 18. 公開リスト詳細画面

例：

```text
三田市 ○○地区
ゴミの日

作成者：○○

────────────────

🔥 燃えるゴミ
毎週 火・金

♻️ プラスチック
毎週 水

🗑️ 燃えないゴミ
第2木曜日

────────────────

通知
おすすめ：前日の20:00

[ このリストを登録 ]
```

MVPでは登録操作を1ボタンで完了できることを重視する。

---

# 19. 購読

ユーザーが公開Categoryを登録するとSubscriptionが作成される。

Subscriptionはサーバーへ保存する。

理由：

- 機種変更
- 複数端末
- 再インストール
- Category更新通知

への対応。

---

# 20. Category更新

作者がItemを変更するとCategoryの`version`を更新する。

例：

```text
version 12
↓
Item変更
↓
version 13
```

購読者の端末は保持しているversionとの差分を検出する。

変更を確認した場合：

```text
最新Categoryを取得
↓
ItemをSQLiteへ保存
↓
古いローカル通知をキャンセル
↓
未来のOccurrenceを再計算
↓
新しい通知をOSへ登録
```

---

# 21. 削除

Category / Itemは同期の都合上、原則Soft Deleteを利用する。

```text
deleted_at
```

を保持する。

これにより購読端末へ、

> Itemが削除された

ことを伝達できる。

削除されたItemに紐づく未来のLocal Notificationは端末側で削除する。

---

# 22. 通知アーキテクチャ

本プロダクトでは、

**リマインダー通知の実行をBackendで行わない。**

サーバーCronも基本的に使用しない。

```text
Item
↓
Schedule Rule
↓
スマートフォンでOccurrence生成
↓
通知日時算出
↓
OSへLocal Notification予約
```

Expoの`expo-notifications`はiOS/Android双方で日時指定のLocal NotificationとPush Notificationを扱える。

---

# 23. Notification Scheduler

端末にはNotification Schedulerを実装する。

責務：

```text
Schedule Rule
↓
未来のOccurrence一覧を生成
↓
ユーザー設定から通知日時を計算
↓
近い通知を抽出
↓
OS Notification Schedulerへ登録
```

---

# 24. Notification Horizon

1年分等の通知を一度にOSへ登録しない。

初期値：

```text
計算対象期間：60日
OSへの最大登録数：48件
```

とする。

近い順に最大48件を登録する。

残りは、

- アプリ起動
- データ同期
- Background Task

等のタイミングで補充する。

値は実機検証後に調整可能とする。

---

# 25. Local Notification ID

通知には決定論的な識別子を割り当てる。

概念：

```text
itemId
+
occurrenceAt
+
reminderRule
```

から生成する。

これにより予定更新時、

```text
古い通知
↓
ID特定
↓
cancel
↓
新しい通知登録
```

を安全に行える。

---

# 26. Push Notificationの役割

Pushは通常のReminderには使用しない。

Pushの役割は、

> **Categoryが更新されたことを通知する**

ことである。

例：

```json
{
  "type": "category.updated",
  "categoryId": "...",
  "version": 13
}
```

Push受信後、

```text
Category取得
↓
SQLite更新
↓
Local Notification再生成
```

する。

---

# 27. Pushは同期保証として扱わない

iOS/AndroidのBackground Pushは常に即時実行されるとは限らない。

ExpoもBackground NotificationについてOSによる実行・配送保証がないことを明記している。

したがって同期契機は複数持つ。

```text
アプリ起動
フォアグラウンド復帰
Push受信
Background Task
手動更新
```

---

# 28. オフライン対応

通知とホーム画面はネットワーク接続なしでも動作可能にする。

端末のSQLiteに、

- Category
- Item
- Subscription
- Notification Preference
- Schedule情報

をキャッシュする。

Expo SQLiteはiOS/Android双方で永続SQLite DBを利用できる。

---

# 29. サーバーと端末の責務

## Server

**データの正本。**

保存：

- User
- Category
- Item
- Subscription
- Device
- 将来的なPurchase / Entitlement

担当：

- 認証
- CRUD
- 公開
- 検索
- 購読
- 同期
- Category更新通知

---

## Smartphone

**予定計算と通知の実行主体。**

担当：

- SQLite Cache
- Schedule計算
- Occurrence生成
- Local Notification
- ユーザー固有通知設定
- オフライン表示
- 同期

---

# 30. Frontend技術選定

## 採用

**React Native + Expo**

とする。

具体構成：

```text
React Native
Expo
Expo Router
TypeScript
expo-notifications
expo-sqlite
expo-secure-store
expo-background-task
```

---

# 31. React Native + Expo採用理由

今回のアプリは、

- iOS
- Android
- Local Notification
- Push Notification
- SQLite
- Deep Link
- Background Sync
- 将来的なIn-App Purchase

などモバイル固有機能への依存が大きい。

ExpoはLocal/Push Notificationを公式に扱い、Expo SQLiteもiOS/Android双方に対応している。

そのためFlutterやTauriよりも、

**TypeScriptを維持しながらモバイルネイティブ機能を扱いやすい**

React Native + Expoを採用する。

---

# 32. Expo Router

NavigationにはExpo Routerを使用する。

Expo RouterはExpoの新規プロジェクトで推奨されており、File-based Routing・Typed Routes・Deep Linkを標準的に扱える。

ルート例：

```text
src/app/

├── _layout.tsx
├── (tabs)/
│   ├── index.tsx
│   ├── discover.tsx
│   └── lists.tsx
│
├── lists/
│   ├── new.tsx
│   └── [categoryId]/
│       ├── index.tsx
│       └── edit.tsx
│
├── items/
│   ├── new.tsx
│   └── [itemId].tsx
│
└── settings/
    └── index.tsx
```

---

# 33. Frontend State設計

MVPでは巨大なGlobal Stateライブラリを導入しない。

状態を以下に分類する。

### Persistent Data

SQLite。

- Category
- Item
- Subscription

### Authentication Secret

SecureStore。

- Access Token
- Refresh Token等

Expo SecureStoreはAndroidではKeystore、iOSではKeychainを利用する。

### UI State

ReactのState / Context。

例：

- Modal表示
- 作成途中フォーム
- Theme
- Authentication State

---

# 34. Backend技術選定

Backend：

```text
PHP
Slim Framework 4
MySQL 8.x
PDO
REST API
```

SlimはHTTP/APIレイヤーとして利用する。

Slim自身にDomain Logicを持たせない。

Slim 4はAPI構築向けのミニマルなPHP Frameworkとして提供されている。

開発時点では最新Security Patchを適用したSlimを利用する。

2026年8月現在、Slim 4.15.2ではCVE-2026-48157への修正が含まれている。

---

# 35. Backend Architecture

```text
Route
↓
Controller
↓
Service
↓
Repository
↓
PDO
↓
MySQL
```

---

# 36. Controller

担当：

- HTTP Request解析
- Validation結果受取
- Service呼び出し
- HTTP Response生成

担当しない：

- SQL
- Business Logic
- Notification計算

---

# 37. Service

Business Logicを担当。

例：

```text
CategoryService

create()
update()
publish()
delete()

ItemService

create()
update()
delete()

SubscriptionService

subscribe()
unsubscribe()
```

Item変更時には、

```text
Item更新
+
Category version increment
+
Update Push生成
```

をServiceでまとめて処理する。

---

# 38. Repository

DB Accessを担当。

例：

```text
UserRepository
CategoryRepository
ItemRepository
SubscriptionRepository
DeviceRepository
```

PDOをRepository内部へ閉じ込める。

---

# 39. Backend構成案

```text
backend/

├── public/
│   └── index.php
│
├── src/
│   ├── Controller/
│   ├── Service/
│   ├── Repository/
│   ├── Domain/
│   ├── Middleware/
│   ├── Validation/
│   └── Infrastructure/
│
├── routes/
├── config/
├── migrations/
├── tests/
└── composer.json
```

---

# 40. ID

公開APIで使用するEntity IDにはUUID系IDを使用する。

理由：

- Client側との同期
- URL推測対策
- 将来の分散環境
- Offline Create拡張

を容易にするため。

連番DB IDを外部APIへ直接公開しない。

---

# 41. MySQL Data Model

MVP主要テーブル：

```text
users
categories
items
subscriptions
devices
```

---

# 42. users

```text
id
display_name
email
created_at
updated_at
```

必要以上の個人情報は保存しない。

---

# 43. categories

```text
id
owner_id
name
description
visibility
timezone
version
recommended_reminder
created_at
updated_at
deleted_at
```

`visibility`：

```text
private
unlisted
public
```

---

# 44. items

```text
id
category_id
name
description
schedule_type
schedule_rule
location
url
sort_order
created_at
updated_at
deleted_at
```

`schedule_rule` はMySQL JSON型を利用する。

---

# 45. Schedule Rule JSON

Weekly例：

```json
{
  "weekdays": [2, 5],
  "time": "08:00"
}
```

Monthly Nth Weekday例：

```json
{
  "nth": 2,
  "weekday": 4,
  "time": "08:00"
}
```

Once例：

```json
{
  "at": "2026-09-30T18:00:00+09:00"
}
```

---

# 46. subscriptions

```text
id
user_id
category_id
created_at
```

制約：

```text
UNIQUE(user_id, category_id)
```

ユーザー固有の通知設定はMVPではここには保存しない。

端末SQLiteへ保存する。

---

# 47. devices

```text
id
user_id
platform
push_token
last_seen_at
created_at
updated_at
```

platform：

```text
ios
android
```

---

# 48. Local SQLite

端末には概ね以下を持つ。

```text
categories
items
subscriptions
notification_preferences
scheduled_notifications
sync_state
```

Server Schemaと完全一致させる必要はない。

Client用途に最適化する。

---

# 49. Sync

ClientはSync Cursorを保存する。

概念：

```text
GET /v1/sync?cursor=...
```

Response：

```json
{
  "categories": [],
  "items": [],
  "subscriptions": [],
  "nextCursor": "..."
}
```

変更されたレコードだけを返す。

---

# 50. Sync対象

Clientが必要とするのは、

- 自分が所有するCategory
- 自分が購読するCategory
- それらに所属するItem

のみ。

全公開Categoryを端末へ同期しない。

「探す」の検索結果はAPI経由で取得する。

---

# 51. API

Base：

```text
/api/v1
```

---

## Auth

```text
POST /auth/login
POST /auth/refresh
POST /auth/logout
GET  /me
```

---

## Category

```text
POST   /categories
GET    /categories/{id}
PATCH  /categories/{id}
DELETE /categories/{id}

GET /categories/search
```

---

## Item

```text
POST   /categories/{categoryId}/items
PATCH  /items/{itemId}
DELETE /items/{itemId}
```

---

## Subscription

```text
POST   /categories/{categoryId}/subscribe
DELETE /categories/{categoryId}/subscribe

GET /me/subscriptions
```

---

## Device

```text
POST   /devices
DELETE /devices/{id}
```

---

## Sync

```text
GET /sync
```

---

# 52. API Response

成功Response形式は極端な独自ラッパーを使用しない。

例：

```json
{
  "id": "...",
  "name": "ゴミの日"
}
```

Errorは統一形式にする。

```json
{
  "error": {
    "code": "CATEGORY_NOT_FOUND",
    "message": "Category not found"
  }
}
```

---

# 53. Authentication

MVPのログイン方式は、

- Apple
- Google

を第一候補とする。

メールアドレス + Passwordを独自管理する必要性は低いため、Social Login中心を想定する。

詳細方式は実装設計時に確定する。

---

# 54. 通知Permission UX

初回起動時には通知Permissionを要求しない。

ユーザーが初めて、

- リストを作成する
- リストを登録する

タイミングで、

```text
この予定を忘れないように
通知をオンにしますか？

[ 通知をオンにする ]
```

という説明画面を表示した後、OS Permissionを要求する。

---

# 55. 通知Permission拒否

通知拒否状態でもアプリは利用可能。

ホーム画面には予定を表示する。

必要に応じて、

```text
通知がオフになっています
```

を表示する。

強制的にPermission要求を繰り返さない。

---

# 56. Deep Link

公開Categoryには共有可能URLを持たせる。

例：

```text
https://example.com/l/{categoryId}
```

アプリインストール済み：

```text
Category Detailを直接開く
```

未インストール：

```text
Web Preview
↓
App Store / Google Play
```

将来的な成長導線として重要。

Expo Routerは各RouteのDeep Link対応を標準的に扱える。

---

# 57. 検索

MVPでは以下から検索可能。

- Category名
- Description

将来的には、

- Creator
- タグ
- 地域
- 人気
- カテゴリ分類

へ拡張する。

---

# 58. 公開コンテンツの安全対策

Public CategoryにはReport機能を提供する。

Report理由例：

```text
スパム
誤情報
なりすまし
不適切な内容
その他
```

公開検索に出す以上、最低限のModeration手段を持つ。

---

# 59. 誤情報

本サービスの公開予定はユーザー投稿情報を含む。

公式情報とユーザー投稿を将来的には区別可能にする。

例：

```text
公式
認証済み
コミュニティ作成
```

MVPでは公式認証機能は不要。

---

# 60. Marketplace

MVPでは実装しない。

ただしCategory単位で販売可能になることを前提にデータモデルを設計する。

将来的には、

```text
Category
↓
Product
↓
Purchase
↓
Entitlement
```

を追加する。

---

# 61. Marketplace料金モデル

現時点の基本案：

**買い切り。**

例：

```text
○○ WORLD TOUR 2027
¥300
```

購入後、そのCategoryの更新を受け取る。

---

# 62. Edition

永久更新義務を避けるため、

```text
2027年度
2028年度

2027 TOUR
2028 TOUR
```

のように別Categoryとして販売可能にする。

---

# 63. Marketplace決済

以下はPRD v0.1では未確定。

- App Store IAP
- Google Play Billing
- Stripe
- Creator payout
- Marketplace手数料
- 資金移動業該当性
- 税務
- 未成年出品者
- 返金

Marketplace実装開始前に法務・決済スキームを別途設計する。

---

# 64. Google Calendar

MVP対象外。

将来的には、

```text
Google Calendarに追加
```

を提供可能。

ただしGoogle CalendarをPrimary Data Sourceにはしない。

本サービスのCategory / Itemを正とする。

---

# 65. Non-Functional Requirements

## Performance

アプリ起動後、SQLiteにデータがある場合はネットワークを待たずホームを表示する。

目標：

```text
Cold Start → 初期コンテンツ表示
2秒以内を目標
```

---

## Availability

通知実行はLocal Notificationであるため、Backend障害中でも予約済み通知は動作する。

---

## Scalability

通常Reminderの件数増加がBackend処理量に比例しない構造とする。

100万人が同じ予定を購読していても、

毎日の100万通知をServer Cronから送信する構造にはしない。

---

# 66. Security

最低限以下を実施する。

- HTTPS Only
- Authorization確認
- 所有Categoryの更新権限確認
- SQL Parameter Binding
- Rate Limit
- Input Validation
- URL Validation
- Output Escape
- Access TokenのSecureStore保存
- SecretをSQLiteへ保存しない

---

# 67. Authorization

例：

```text
PATCH /items/{id}
```

では必ず、

```text
item.category.owner_id === currentUser.id
```

を確認する。

Clientから送られたowner_id等を信用しない。

---

# 68. Privacy

Private Categoryは、

- 検索
- Public API
- 他ユーザー

からアクセス不可能にする。

Unlisted CategoryはURLを知っているユーザーだけアクセス可能。

---

# 69. Analytics

MVPで確認したい主要指標：

### Activation

初回起動から、

> 最初の通知が予約される

まで到達したユーザー割合。

---

### Subscription

公開Category Detailを閲覧したユーザーのうち、

> リストを登録した割合。

---

### Retention

7日 / 30日後も登録Categoryを保持している割合。

---

### Notification Engagement

通知からアプリを開いた割合。

---

### Creator Activity

作成されたCategory数。

公開されたCategory数。

---

# 70. MVP Scope

MVPに含める。

### Account

- Login
- Logout
- User Profile最低限

### List

- Create
- Edit
- Delete
- Public / Private / Unlisted

### Item

- Create
- Edit
- Delete
- Recurrence Rule

### Notification

- Local Notification
- Reminder設定
- Item別ON/OFF
- Permission管理

### Subscription

- Public List Detail
- Subscribe
- Unsubscribe

### Discover

- Search

### Sync

- SQLite Cache
- Delta Sync
- Category Version
- Update Push

### Safety

- Public List Report

---

# 71. MVP対象外

以下はMVPには含めない。

- 有料販売
- Creator payout
- Stripe
- App Store Purchase
- Google Play Purchase
- Google Calendar連携
- コメント
- Follow
- Like
- Creator Analytics
- Official Account Verification
- Web版フルアプリ
- 複雑なSocial機能

---

# 72. MVP主要User Flow

## Flow A：自分用ゴミの日

```text
アプリ起動
↓
マイリスト
↓
新しいリスト
↓
「ゴミの日」
↓
予定追加
↓
「燃えるゴミ」
↓
毎週 火・金
↓
前日の夜
↓
保存
↓
OSへLocal Notification予約
```

---

## Flow B：公開ゴミリスト購読

```text
探す
↓
「三田市 ゴミ」
↓
Category Detail
↓
このリストを登録
↓
おすすめ通知設定確認
↓
SQLite保存
↓
Occurrence生成
↓
Local Notification予約
```

---

## Flow C：作者が日程変更

```text
作者
↓
Item編集
↓
Backend
↓
Item UPDATE
↓
Category Version++
↓
購読端末へUpdate Push
↓
端末Sync
↓
古い通知Cancel
↓
新しい通知Schedule
```

---

# 73. Acceptance Criteria

MVP完成条件。

### リスト

ユーザーが5分以内に、

```text
ゴミの日
├ 燃えるゴミ：毎週火金
└ プラスチック：毎週水
```

を作成できる。

### 通知

アプリを閉じた状態でもLocal Notificationが表示される。

### Offline

ネット接続なしでも、既に同期済みの今後予定を閲覧できる。

### Public

公開Categoryを別ユーザーが検索・登録できる。

### Sync

作者がItemを変更した後、購読端末が次回同期した際に変更が反映される。

### Reschedule

変更前のLocal Notificationが残らない。

### Permission

通知Permissionを拒否してもアプリ自体は使用可能。

### Cross Platform

同一主要機能がiOS/Android双方で動作する。

---

# 74. Testing

## Unit Test

重点：

- Schedule Rule
- Occurrence生成
- Reminder計算
- Time Zone
- 第N曜日
- 月末
- Leap Year
- DST

Schedule EngineはUIから独立したPure TypeScriptとしてテストする。

---

## Backend Unit Test

重点：

- Category Permission
- Item Permission
- Subscribe
- Version Increment
- Visibility
- Sync

---

## Integration Test

```text
Slim
+
MySQL
```

でAPI Test。

---

## Mobile Integration Test

重点：

```text
SQLite
↓
Schedule Engine
↓
Notification Scheduler
```

---

## 実機Test

通知については、

- iPhone
- Android

両方の実機で検証する。

Push機能を含むExpo NotificationsはDevelopment Buildを使用する。Expoでは現在、Push NotificationはExpo Goだけでは完結せずDevelopment Buildが必要。

---

# 75. 実装フェーズ

## Phase 1

Local Prototype。

```text
React Native
SQLite
Category
Item
Schedule Engine
Local Notification
```

Backendなしでも動く状態を作る。

---

## Phase 2

Backend。

```text
Slim
MySQL
Auth
Category Sync
Item Sync
```

---

## Phase 3

公開機能。

```text
Public Category
Search
Subscription
Sharing
```

---

## Phase 4

Update Sync。

```text
Category Version
Push
Reschedule
Background Sync
```

---

## Phase 5

Production Preparation。

```text
Moderation
Analytics
Crash Reporting
Rate Limit
Store Release
```

---

# 76. 将来機能

候補：

- Schedule Marketplace
- Paid Category
- Creator revenue
- Google Calendar
- Official Accounts
- Category Rating
- Follow
- Category Fork
- Category Template
- Web Preview
- 地域自動提案
- AIによる予定抽出
- URLからイベント予定生成
- QRコード購読
- Widget
- Apple Watch / Wear OS
- Discord等外部通知
- 家族共有

---

# 77. Product Principles

開発中に迷った場合は以下を優先する。

### 1. カレンダーにしない

「予定を管理する機能」を増やしすぎない。

### 2. 入力を減らす

自分で入力するより、

> 登録するだけ

を理想とする。

### 3. 通知設定を難しくしない

高度な設定は後から開ける。

### 4. Backendへ通知責務を持たせない

通常Reminderは端末で処理する。

### 5. MarketplaceをMVPへ入れない

まず、

> 「予定を購読する」

という体験自体に価値があるか検証する。

---

# 78. 技術スタック最終決定

```text
Mobile
────────────────
React Native
Expo
Expo Router
TypeScript

Local Data
────────────────
expo-sqlite

Notifications
────────────────
expo-notifications

Secure Storage
────────────────
expo-secure-store

Background Sync
────────────────
expo-background-task

Backend
────────────────
PHP
Slim Framework 4

Database
────────────────
MySQL 8.x

DB Access
────────────────
PDO
Repository Pattern

API
────────────────
REST
JSON
HTTPS
```

---

# 79. Architecture Summary

```text
                 ┌──────────────────┐
                 │      MySQL       │
                 └────────▲─────────┘
                          │
                 ┌────────┴─────────┐
                 │   Slim Backend   │
                 │                  │
                 │ User             │
                 │ Category         │
                 │ Item             │
                 │ Subscription     │
                 │ Device           │
                 └────────┬─────────┘
                          │
                       REST API
                          │
                 ┌────────▼─────────┐
                 │ React Native App │
                 │                  │
                 │ SQLite           │
                 │ Sync Engine      │
                 │ Schedule Engine  │
                 │ Notification     │
                 │ Scheduler        │
                 └────────┬─────────┘
                          │
                 OS Local Notification
                      ┌───┴───┐
                      │       │
                     iOS   Android
```

最重要な責務分離：

```text
Server
=
予定データを保存・共有・同期する


Smartphone
=
予定を計算し、ユーザーへ通知する
```

この原則をMVPから維持する。

