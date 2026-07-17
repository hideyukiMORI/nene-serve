import { useCreatives, type Creative } from '@/entities/creative'
import { mapProblemDetailsToMessageKey, type MessageKey } from '@/shared/i18n'

export interface CreativesPage {
  creatives: Creative[]
  loading: boolean
  errorKey: MessageKey | null
}

export function useCreativesPage(): CreativesPage {
  const query = useCreatives()
  return {
    creatives: query.data ?? [],
    loading: query.isPending,
    errorKey: query.error !== null ? mapProblemDetailsToMessageKey(query.error) : null,
  }
}
