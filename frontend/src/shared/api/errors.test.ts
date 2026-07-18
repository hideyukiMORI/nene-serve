import { describe, expect, it } from 'vitest'
import { AppError, parseProblemDetails } from './errors'
import type { ProblemDetails } from './errors'

const problem: ProblemDetails = {
  type: 'https://nene.example/problems/validation',
  title: 'Validation failed',
  status: 422,
  detail: 'One or more fields are invalid.',
  instance: '/admin/campaigns',
  errors: [{ field: 'name', message: 'Name is required.', code: 'required' }],
}

describe('AppError', () => {
  it('maps every problem-details field and reads as an Error', () => {
    const error = new AppError(problem)

    expect(error).toBeInstanceOf(Error)
    expect(error.name).toBe('AppError')
    expect(error.message).toBe('Validation failed')
    expect(error.type).toBe('https://nene.example/problems/validation')
    expect(error.title).toBe('Validation failed')
    expect(error.status).toBe(422)
    expect(error.detail).toBe('One or more fields are invalid.')
    expect(error.instance).toBe('/admin/campaigns')
    expect(error.errors).toEqual([
      { field: 'name', message: 'Name is required.', code: 'required' },
    ])
  })

  it('leaves detail and errors undefined when the problem omits them', () => {
    const error = new AppError({
      type: 'about:blank',
      title: 'Not found',
      status: 404,
      instance: '/admin/campaigns/42',
    })

    expect(error.detail).toBeUndefined()
    expect(error.errors).toBeUndefined()
  })

  it.each([500, 503, 429])('treats status %i as retryable', (status) => {
    expect(new AppError({ ...problem, status }).isRetryable).toBe(true)
  })

  it.each([400, 404, 422])('treats status %i as not retryable', (status) => {
    expect(new AppError({ ...problem, status }).isRetryable).toBe(false)
  })
})

describe('parseProblemDetails', () => {
  it('builds an AppError from an application/problem+json body', async () => {
    const response = new Response(JSON.stringify(problem), {
      status: 422,
      headers: { 'Content-Type': 'application/problem+json' },
    })

    const error = await parseProblemDetails(response)

    expect(error.status).toBe(422)
    expect(error.title).toBe('Validation failed')
    expect(error.errors).toHaveLength(1)
  })

  it('falls back to statusText and about:blank when the body is not JSON', async () => {
    const response = new Response('<html>gateway timeout</html>', {
      status: 504,
      statusText: 'Gateway Timeout',
    })

    const error = await parseProblemDetails(response)

    expect(error.type).toBe('about:blank')
    expect(error.title).toBe('Gateway Timeout')
    expect(error.status).toBe(504)
    expect(error.instance).toBe(response.url)
  })

  it('labels the fallback "Request failed" when statusText is empty', async () => {
    const response = new Response('not json', { status: 500 })

    const error = await parseProblemDetails(response)

    expect(error.title).toBe('Request failed')
    expect(error.isRetryable).toBe(true)
  })
})
