/** Wire shapes for admin creative endpoints (snake_case, the admin projection). */
export interface CreativeDto {
  id: string
  type: string
  review_status: string
  version: number
  destination_url?: string
  asset_url?: string | null
  width?: number | null
  height?: number | null
  review_reason?: string | null
}

export interface CreativeListDto {
  creatives: CreativeDto[]
}
