import type { AdvertiserDto } from './api-types'
import type { Advertiser } from './model'

export function mapAdvertiserDtoToModel(dto: AdvertiserDto): Advertiser {
  return { id: dto.id, name: dto.name, status: dto.status }
}
