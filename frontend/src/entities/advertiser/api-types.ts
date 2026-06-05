import type { Paginated } from '@/shared/api/pagination'

export interface AdvertiserDto {
  id: string
  name: string
  status: string
  invoice_client_id?: string | null
}

export type AdvertiserListDto = Paginated<AdvertiserDto>
