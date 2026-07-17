import {
  useReviewQueue,
  useReviewTransition,
  type Creative,
  type ReviewAction,
} from '@/entities/creative'
import { mapProblemDetailsToMessageKey, type MessageKey } from '@/shared/i18n'

export interface ReviewPage {
  creatives: Creative[]
  loading: boolean
  errorKey: MessageKey | null
  acting: boolean
  actionErrorKey: MessageKey | null
  act: (id: string, action: ReviewAction) => Promise<boolean>
}

export function useReviewPage(): ReviewPage {
  const query = useReviewQueue()
  const transition = useReviewTransition()

  return {
    creatives: query.data ?? [],
    loading: query.isPending,
    errorKey: query.error !== null ? mapProblemDetailsToMessageKey(query.error) : null,
    acting: transition.isPending,
    actionErrorKey:
      transition.error !== null ? mapProblemDetailsToMessageKey(transition.error) : null,
    act: async (id: string, action: ReviewAction): Promise<boolean> => {
      try {
        await transition.mutateAsync({ id, action })
        return true
      } catch {
        return false
      }
    },
  }
}
