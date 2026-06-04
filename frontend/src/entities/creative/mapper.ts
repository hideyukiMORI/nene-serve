import type { CreativeDto } from './api-types'
import type { Creative } from './model'

export function mapCreativeDtoToModel(dto: CreativeDto): Creative {
  return {
    id: dto.id,
    type: dto.type,
    reviewStatus: dto.review_status,
    version: dto.version,
  }
}
