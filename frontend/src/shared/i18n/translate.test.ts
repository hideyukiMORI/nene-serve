import { describe, expect, it } from 'vitest'
import { en } from './messages/en'
import { translate } from './translate'

describe('translate', () => {
  it('returns the catalog value when the key is present', () => {
    expect(
      translate({ 'common.error.unknown': 'Etwas ist schiefgelaufen.' }, 'common.error.unknown'),
    ).toBe('Etwas ist schiefgelaufen.')
  })

  it('falls back to the English source of truth when the key is missing', () => {
    expect(translate({}, 'common.error.unknown')).toBe(en['common.error.unknown'])
  })

  it('interpolates string and number params', () => {
    const result = translate(
      { 'common.error.unknown': '{{name}} failed {{count}} times' },
      'common.error.unknown',
      { name: 'Sync', count: 3 },
    )
    expect(result).toBe('Sync failed 3 times')
  })

  it('leaves placeholders untouched when their param is missing', () => {
    const result = translate(
      { 'common.error.unknown': '{{name}} and {{other}}' },
      'common.error.unknown',
      { name: 'Sync' },
    )
    expect(result).toBe('Sync and {{other}}')
  })

  it('returns the raw string, placeholders included, when no params are given', () => {
    expect(translate({ 'common.error.unknown': 'Keep {{this}}' }, 'common.error.unknown')).toBe(
      'Keep {{this}}',
    )
  })
})
