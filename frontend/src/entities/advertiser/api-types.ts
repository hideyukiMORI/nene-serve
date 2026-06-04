export interface AdvertiserDto {
  id: string
  name: string
  status: string
  invoice_client_id?: string | null
}

export interface AdvertiserListDto {
  advertisers: AdvertiserDto[]
}
