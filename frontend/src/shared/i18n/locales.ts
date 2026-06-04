/**
 * i18n locales — six application locales (ADR 0011): English (source of truth,
 * ADR 0008), Japanese, Simplified Chinese, Korean, German, Spanish. Keep in
 * parity with the server-side catalogs in `locales/*.json`.
 */

export type SupportedLocale = 'en' | 'ja' | 'zh-Hans' | 'ko' | 'de' | 'es'

export interface LocaleMeta {
  /** Native language name shown in the locale selector. */
  label: string
  /** Text direction. */
  dir: 'ltr' | 'rtl'
}

export const LOCALES: Record<SupportedLocale, LocaleMeta> = {
  en: { label: 'English', dir: 'ltr' },
  ja: { label: '日本語', dir: 'ltr' },
  'zh-Hans': { label: '简体中文', dir: 'ltr' },
  ko: { label: '한국어', dir: 'ltr' },
  de: { label: 'Deutsch', dir: 'ltr' },
  es: { label: 'Español', dir: 'ltr' },
}

export const DEFAULT_LOCALE: SupportedLocale = 'en'

export const SUPPORTED_LOCALE_IDS = Object.keys(LOCALES) as SupportedLocale[]

/**
 * Resolve a raw locale string (localStorage / navigator.language) to a
 * supported locale, falling back to the default. Examples: `en-US` → `en`,
 * `ja-JP` → `ja`, `zh-Hans-CN` → `zh-Hans`, `zh` → `zh-Hans`, `fr` → `en`.
 */
export function resolveLocale(raw: string): SupportedLocale {
  if (SUPPORTED_LOCALE_IDS.includes(raw as SupportedLocale)) {
    return raw as SupportedLocale
  }
  if (raw.toLowerCase().startsWith('zh')) {
    return 'zh-Hans'
  }
  const prefix = raw.split('-')[0]
  if (prefix !== undefined && SUPPORTED_LOCALE_IDS.includes(prefix as SupportedLocale)) {
    return prefix as SupportedLocale
  }
  return DEFAULT_LOCALE
}
