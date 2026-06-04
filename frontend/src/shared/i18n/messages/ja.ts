import type { MessageCatalog } from './en'

/** Japanese catalog (ADR 0011). */
export const ja: MessageCatalog = {
  'app.title': 'NeNe Serve',
  'app.subtitle': '広告配信と分析',

  'nav.placements': 'プレースメント',

  'shell.theme': 'テーマ',
  'shell.themeLight': 'ライト',
  'shell.themeDark': 'ダーク',
  'shell.lang': '言語',
  'shell.signout': 'サインアウト',

  'common.error.unauthorized': '続行するにはサインインしてください。',
  'common.error.forbidden': 'このリソースへのアクセス権がありません。',
  'common.error.notFound': '見つかりませんでした。',
  'common.error.conflict': '現在の状態と競合しています。',
  'common.error.validation': '入力内容を確認して再度お試しください。',
  'common.error.rateLimit': 'リクエストが多すぎます。しばらくして再試行してください。',
  'common.error.serverError': 'サーバー側で問題が発生しました。',
  'common.error.unknown': '予期しないエラーが発生しました。',

  'login.title': 'サインイン',
  'login.subtitle': '管理コンソール',
  'login.email': 'メールアドレス',
  'login.password': 'パスワード',
  'login.submit': 'サインイン',
  'login.failed': 'メールアドレスまたはパスワードが正しくありません。',
  'login.secure': '暗号化・監査された接続。',
  'login.validation.emailRequired': 'メールアドレスを入力してください。',
  'login.validation.passwordRequired': 'パスワードを入力してください。',

  'placements.title': 'プレースメント',
  'placements.subtitle': '広告枠と配信ステータス',
  'placements.empty': 'プレースメントはまだありません。',
  'placements.loading': '読み込み中…',
  'placements.loadError': 'プレースメントを読み込めませんでした。',
  'placements.column.key': 'キー',
  'placements.column.status': 'ステータス',
  'placements.column.creative': '既定クリエイティブ',

  'notFound.title': 'ページが見つかりません',
  'notFound.body': 'お探しのページは存在しません。',
  'notFound.back': 'プレースメントへ戻る',
}
