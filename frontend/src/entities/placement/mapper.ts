import type { PlacementDto } from './api-types'
import type { Placement } from './model'

export function mapPlacementDtoToModel(dto: PlacementDto): Placement {
  return {
    id: dto.id,
    publicKey: dto.public_placement_key,
    status: dto.status,
    defaultCreativeId: dto.default_creative_id ?? null,
    archivedAt: dto.archived_at ?? null,
  }
}
