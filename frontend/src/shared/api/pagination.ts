/**
 * Standard list-endpoint envelope served by the NENE2 runtime
 * (`PaginationResponse`): `{ items, limit, offset, total? }`.
 */
export interface Paginated<T> {
  items: T[]
  limit: number
  offset: number
  total?: number | null
}
