import { describe, expect, it } from 'vitest'
import { mapUserDtoToModel } from './mapper'

describe('user mapper', () => {
  it('maps the wire dto to the ui model', () => {
    expect(
      mapUserDtoToModel({ id: 'u1', organization_id: 'o1', email: 'a@b.test', role: 'analyst' }),
    ).toEqual({ id: 'u1', email: 'a@b.test', role: 'analyst' })
  })
})
