import { en, type MessageCatalog } from './messages/en'

export type MessageKey = keyof MessageCatalog
export type MessageParams = Record<string, string | number>

/**
 * Look up a message key in the given catalog, falling back to the English
 * source of truth, and interpolate `{{param}}` placeholders.
 */
export function translate(
  messages: Partial<MessageCatalog>,
  key: MessageKey,
  params?: MessageParams,
): string {
  const raw: string = messages[key] ?? en[key]
  if (params === undefined || Object.keys(params).length === 0) return raw
  return raw.replace(/\{\{(\w+)\}\}/g, (match, name: string) =>
    name in params ? String(params[name]) : match,
  )
}
