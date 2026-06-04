import type { PlacementDto } from '@/entities/placement/api-types'

export function makePlacementDto(overrides: Partial<PlacementDto> = {}): PlacementDto {
  return {
    id: 'plc-1',
    public_placement_key: 'pk_default',
    status: 'active',
    default_creative_id: 'cr-1',
    archived_at: null,
    ...overrides,
  }
}
