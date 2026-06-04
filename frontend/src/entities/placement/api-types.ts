/** Wire shapes for the admin placements endpoint (snake_case, as served). */
export interface PlacementDto {
  id: string
  public_placement_key: string
  status: string
  default_creative_id?: string | null
  archived_at?: string | null
}

export interface PlacementListDto {
  placements: PlacementDto[]
}
