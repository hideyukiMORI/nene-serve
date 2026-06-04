/** UI model for a placement (camelCase). */
export interface Placement {
  id: string
  publicKey: string
  status: string
  defaultCreativeId: string | null
  archivedAt: string | null
}
