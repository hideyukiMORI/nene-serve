import type { SupportedLocale } from './locales'

/**
 * CSS custom property Tailwind v4 reads for `font-sans`. Setting it inline on
 * the document element overrides the `@theme` declaration so the active locale
 * gets a script-appropriate font stack. (Latin fonts are bundled via
 * @fontsource; CJK/Hangul fall back to the platform's system fonts for now —
 * bundling Noto Sans SC/KR is a follow-up.)
 */
export const ADMIN_FONT_FAMILY_VAR = '--font-sans'

const LATIN = '"Inter", ui-sans-serif, system-ui, sans-serif'
const JP = '"Noto Sans JP", "Hiragino Sans", "Yu Gothic UI", sans-serif'
const SC = '"Noto Sans SC", "PingFang SC", "Microsoft YaHei", sans-serif'
const KR = '"Noto Sans KR", "Apple SD Gothic Neo", "Malgun Gothic", sans-serif'

const LOCALE_FONT_STACKS: Record<SupportedLocale, string> = {
  en: LATIN,
  ja: JP,
  'zh-Hans': SC,
  ko: KR,
  de: LATIN,
  es: LATIN,
}

export function getLocaleFontStack(locale: SupportedLocale): string {
  return LOCALE_FONT_STACKS[locale]
}

export function applyLocaleFontFamily(
  locale: SupportedLocale,
  root: HTMLElement = document.documentElement,
): void {
  root.style.setProperty(ADMIN_FONT_FAMILY_VAR, getLocaleFontStack(locale))
}
