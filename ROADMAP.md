# Yotee ロードマップ

PRD（[予定購読・通知アプリ 詳細PRD.md](予定購読・通知アプリ%20詳細PRD.md)）§75「実装フェーズ」を、着手可能なタスク単位まで分解したもの。フェーズは順番に進める前提（Phase 1 が動かないまま Phase 2 に進まない）。各フェーズの完了条件（Exit Criteria）を満たしたら次へ進む。

進捗に応じてチェックボックスを更新していく想定のドキュメント。

**GitHub Issue運用ルール：** 次フェーズのIssueは前もってまとめて起票しない。現フェーズが完了した時点で、次フェーズの項目をIssue化する（`phase-N`ラベル + `Phase N`マイルストーンを付与）。現時点でIssue化済みなのは `Phase 1`（[milestone](https://github.com/tanahiro2010/yotee/milestone/1)）のみ。

---

## Phase 1 — ローカルプロトタイプ（バックエンドなし）

**目的：** サーバーなしで「リスト作成→予定追加→通知予約」が端末単体で完結することを証明する。PRD §22/§65/§77 の核心（通知実行はバックエンドが担わない）を最初から体現する土台。

- [ ] Expo + TypeScript + Expo Router でプロジェクト初期化（`src/app/` 構成、§32）
- [ ] `expo-sqlite` セットアップ、ローカルスキーマ作成（`categories` / `items`、§48 準拠だがサーバースキーマと一致させる必要はない）
- [ ] Schedule Engine を Pure TypeScript で実装（UI非依存、単体テスト可能に。§74最重要領域）
  - [ ] `Once` ルールの Occurrence 生成
  - [ ] `Weekly`（曜日配列）の Occurrence 生成
  - [ ] `Monthly Day`（毎月N日、月末処理含む）
  - [ ] `Monthly Nth Weekday`（第N曜日）
  - [ ] `Yearly`（うるう年含む）
  - [ ] タイムゾーン（IANA）+ DST 考慮した現地時刻計算（§9）
  - [ ] 上記全パターンの unit test（weekday境界、月末、うるう年、第N曜日、DST）
- [ ] Notification Scheduler 実装（§23-25）
  - [ ] Occurrence → リマインダー日時への変換（前日の夜/当日の朝/カスタム）
  - [ ] Notification Horizon 実装（60日先まで計算、OS登録上限48件、§24）
  - [ ] 決定論的 Local Notification ID 生成（`itemId + occurrenceAt + reminderRule`、§25）
  - [ ] `expo-notifications` で Local Notification 登録/キャンセル
- [ ] 3タブナビゲーション（ホーム / 探す / マイリスト、§14）— この段階では「探す」はダミーでよい
- [ ] リスト作成 UI（§15）／予定作成 UI（§16）— cron等を出さないシンプルなフォーム
- [ ] ホーム画面：近い予定の時系列表示（§13）
- [ ] 通知 Permission UX（初回起動時に要求しない、リスト作成/登録時に説明→OS要求、§54）。拒否時もアプリ利用可能にする（§55）
- [ ] Cold Start → 初期表示 2秒以内の実測（§65 Performance）

**Exit Criteria（§73 Acceptance Criteria の一部）:**
- ユーザーが5分以内に「ゴミの日（燃えるゴミ：毎週火金、プラスチック：毎週水）」を作成できる
- アプリを閉じた状態でも Local Notification が表示される
- 通知 Permission を拒否してもアプリ自体は使用可能
- iOS/Android 実機で同一動作を確認（§74 実機Test）

---

## Phase 2 — バックエンド構築

**目的：** データの正本をサーバーへ移し、認証とCRUD・同期の基盤を作る。まだ公開機能・購読機能はなし（自分のリストのみ）。

- [ ] Slim 4 + PHP プロジェクト初期化（`backend/` 構成、§39）。Slim は最新セキュリティパッチ版を使用（CVE-2026-48157 対応含む最新版を確認、§34）
- [ ] MySQL 8 スキーマ作成・migration（`users` / `categories` / `items` / `subscriptions` / `devices`、§42-47）
  - [ ] `schedule_rule` JSON カラム設計（§45 の形式に準拠）
  - [ ] 全テーブルに `deleted_at`（Soft Delete、§21）
  - [ ] Category ID / Item ID を UUID系に（§40）
- [ ] レイヤー構成の徹底：`Route → Controller → Service → Repository → PDO`（§35-38）
- [ ] 認証（Apple / Google Social Login、§53）。Access/Refresh Token 発行
- [ ] `POST/GET/PATCH/DELETE /categories` 実装（`private` のみ、まだ`public`検索は繋がない）
- [ ] `POST/PATCH/DELETE /categories/{id}/items` 実装
- [ ] Item更新時に `Category.version` をアトミックにインクリメントする Service ロジック（§37, まだPush連携なしでOK）
- [ ] 認可チェック：`item.category.owner_id === currentUser.id` を全mutatingエンドポイントで検証（§67、クライアント送信のowner情報は信用しない）
- [ ] `GET /sync?cursor=...` 実装（自分の owned Category のみ返す、§49-50）
- [ ] モバイル側：SecureStore への Access/Refresh Token 保存（§33）、SQLite ⇄ サーバー同期処理
- [ ] Backend Unit Test：Permission / Version Increment / Visibility / Sync（§74）
- [ ] Integration Test（Slim + MySQL）

**Exit Criteria:**
- ログインしたユーザーが作成したリストがサーバーに保存され、別端末にログインし直しても復元できる
- Item編集がサーバーに反映され、次回同期でクライアントに伝わる

---

## Phase 3 — 公開・発見・購読機能

**目的：** 他人が作ったリストを検索して購読できるようにする。ここで初めて「予定を自分で作らなくていい」という中核価値が完成する。

- [ ] Category `visibility`（private/unlisted/public）の切り替えUI（§17, §15の「…」メニューから）
- [ ] `GET /categories/search`（Category名 + Description、§57）
- [ ] 「探す」タブの検索UI・結果一覧
- [ ] 公開リスト詳細画面（§18）— 1ボタンで登録できるUXを重視
- [ ] `POST/DELETE /categories/{categoryId}/subscribe`、`GET /me/subscriptions`（§19, §46 UNIQUE制約）
- [ ] 購読時：おすすめ通知設定（`recommended_reminder`）を初期値として端末にコピー（§11）
- [ ] 購読後のOccurrence生成→Local Notification登録（Phase 1のSchedule Engineをそのまま利用）
- [ ] Item別 通知ON/OFF UI（§12）
- [ ] Deep Link（`https://.../l/{categoryId}`）：インストール済みは詳細画面へ、未インストールはWeb Preview→ストア誘導（§56）
- [ ] Report機能（スパム/誤情報/なりすまし/不適切/その他、§58）
- [ ] Sync対象の制御：owned/subscribedのCategoryのみ同期し、全public Categoryは同期しない（§50、探すはAPI直叩き）

**Exit Criteria（§73）:**
- 公開Categoryを別ユーザーが検索・登録できる
- ネット接続なしでも既に同期済みの今後予定を閲覧できる（Offline対応）

---

## Phase 4 — 更新同期（Update Push / Reschedule）

**目的：** 作成者が予定を変更したとき、購読者の端末が確実に（ただしPushだけに依存せず）追従する。

- [ ] Item更新Service：`Item UPDATE` + `Category.version++` + Update Push生成を同一トランザクションで（§37, §72 Flow C）
- [ ] Device登録API（`POST/DELETE /devices`、push_token + platform、§47）
- [ ] `category.updated` Push送信（`{type, categoryId, version}`、§26）— リマインダー本体ではなく「同期トリガー」としてのみ使う
- [ ] クライアント側 Push受信ハンドラ：Category取得→SQLite更新→古いLocal Notificationキャンセル→Occurrence再計算→新Local Notification登録（§20, §26）
- [ ] `expo-background-task` によるバックグラウンド同期（Push配送は保証されないため必須、§27-28）
- [ ] フォアグラウンド復帰時・手動更新時の同期トリガー実装（§27の同期契機を全て網羅：起動/フォアグラウンド復帰/Push受信/BackgroundTask/手動更新）
- [ ] Development Build でのPush実機検証（Expo GoではPush完結しないため、§74）

**Exit Criteria（§73）:**
- 作者がItemを変更した後、購読端末が次回同期した際に変更が反映される
- 変更前のLocal Notificationが残らない（Reschedule）

---

## Phase 5 — 本番準備

**目的：** ストアリリース可能な品質・運用体制に引き上げる。

- [ ] Moderation運用（Reportキューの確認フロー、§58）
- [ ] Analytics実装：Activation / Subscription率 / 7日・30日Retention / Notification Engagement / Creator Activity（§69）
- [ ] Crash Reporting導入
- [ ] Rate Limit（§66）
- [ ] Input Validation / Output Escape / URL Validation の全エンドポイント監査（§66）
- [ ] HTTPS Only確認、SQL Parameter Binding監査
- [ ] Private/Unlisted Categoryのアクセス制御の最終監査（検索・Public API・他ユーザーから遮断、§68）
- [ ] App Store / Google Play ストア掲載審査対応・リリース

**Exit Criteria:**
- 主要フローがiOS/Android両実機で安定動作し、ストア審査を通過してリリースできる状態

---

## フェーズ外（将来機能・MVP対象外）

PRD §60-64, §71, §76 より。着手しない：有料販売/Marketplace、Creator payout、Stripe/App Store/Google Play課金、Google Calendar連携、コメント、Follow、Like、Creator Analytics、公式アカウント認証、Web版フルアプリ、複雑なSocial機能。

Marketplace関連（§60-63）は「データモデルだけ将来を見据えて設計し、実装はしない」— 具体的には `Category → Product → Purchase → Entitlement` を追加できる形を崩さない程度の配慮に留める。
