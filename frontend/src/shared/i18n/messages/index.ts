import type { SupportedLocale } from '../locales'
import type { MessageCatalog } from './en'
import { en } from './en'
import { ja } from './ja'
import { zhHans } from './zh-Hans'
import { ko } from './ko'
import { de } from './de'
import { es } from './es'

const MESSAGES: Record<SupportedLocale, MessageCatalog> = {
  en,
  ja,
  'zh-Hans': zhHans,
  ko,
  de,
  es,
}

export function getMessages(locale: SupportedLocale): MessageCatalog {
  return MESSAGES[locale]
}
