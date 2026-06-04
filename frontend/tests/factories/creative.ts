import type { CreativeDto } from '@/entities/creative/api-types'

export function makeCreativeDto(overrides: Partial<CreativeDto> = {}): CreativeDto {
  return {
    id: 'cr-1',
    type: 'image',
    review_status: 'approved',
    version: 1,
    destination_url: 'https://acme.test/landing',
    asset_url: 'https://cdn.acme.test/banner.png',
    width: 300,
    height: 250,
    review_reason: null,
    ...overrides,
  }
}
