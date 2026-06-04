import { describe, expect, it } from 'vitest'
import { mapLoginInputToDto } from './mapper'

describe('auth mapper', () => {
  it('maps login input to the request dto', () => {
    expect(mapLoginInputToDto({ email: 'admin@acme.test', password: 'password' })).toEqual({
      email: 'admin@acme.test',
      password: 'password',
    })
  })
})
