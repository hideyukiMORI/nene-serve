import { usePlacements, type Placement } from '@/entities/placement'
import { mapProblemDetailsToMessageKey, type MessageKey } from '@/shared/i18n'

export interface PlacementsPage {
  placements: Placement[]
  loading: boolean
  errorKey: MessageKey | null
}

export function usePlacementsPage(): PlacementsPage {
  const query = usePlacements()

  return {
    placements: query.data ?? [],
    loading: query.isPending,
    errorKey: query.error !== null ? mapProblemDetailsToMessageKey(query.error) : null,
  }
}
