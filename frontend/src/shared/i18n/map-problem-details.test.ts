import { describe, expect, it } from 'vitest'
import { AppError } from '@/shared/api/client'
import { mapProblemDetailsToMessageKey } from './map-problem-details'

function errorWithStatus(status: number): AppError {
  return new AppError({
    type: 'about:blank',
    title: 'Request failed',
    status,
    instance: '/admin/campaigns',
  })
}

describe('mapProblemDetailsToMessageKey', () => {
  it.each([
    [401, 'common.error.unauthorized'],
    [403, 'common.error.forbidden'],
    [404, 'common.error.notFound'],
    [409, 'common.error.conflict'],
    [422, 'common.error.validation'],
    [429, 'common.error.rateLimit'],
  ] as const)('maps status %i to %s', (status, key) => {
    expect(mapProblemDetailsToMessageKey(errorWithStatus(status))).toBe(key)
  })

  it.each([500, 503, 599])('maps status %i to the server-error key', (status) => {
    expect(mapProblemDetailsToMessageKey(errorWithStatus(status))).toBe('common.error.serverError')
  })

  it.each([400, 418])('maps unhandled 4xx status %i to the unknown key', (status) => {
    expect(mapProblemDetailsToMessageKey(errorWithStatus(status))).toBe('common.error.unknown')
  })
})
