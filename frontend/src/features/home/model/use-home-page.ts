import { useCreatives } from '@/entities/creative'
import { useMetricsReport } from '@/entities/metrics'
import { usePlacements } from '@/entities/placement'
import { useSmtpSettings } from '@/entities/settings'
import { useUsers } from '@/entities/user'

/** The seven steps of the core serve loop, in canonical order. */
export type HomeStepId =
  | 'smtp'
  | 'invite'
  | 'placement'
  | 'creative'
  | 'approve'
  | 'embed'
  | 'measure'

export interface HomePage {
  /** Ids of the steps that are already complete. */
  done: ReadonlySet<HomeStepId>
}

/**
 * Derives onboarding progress from the live (mock-backed) account state:
 * each setup step is "done" once its underlying resource exists.
 */
export function useHomePage(): HomePage {
  const smtp = useSmtpSettings()
  const users = useUsers()
  const placements = usePlacements()
  const creatives = useCreatives()
  const metrics = useMetricsReport()

  const done = new Set<HomeStepId>()
  if (smtp.data?.configured === true) done.add('smtp')
  if ((users.data?.length ?? 0) > 1) done.add('invite')
  if ((placements.data?.length ?? 0) > 0) done.add('placement')
  if ((creatives.data?.length ?? 0) > 0) done.add('creative')
  if (creatives.data?.some((creative) => creative.reviewStatus === 'approved') === true) {
    done.add('approve')
  }
  if ((metrics.data?.totals.serveRequests ?? 0) > 0) done.add('embed')
  if ((metrics.data?.totals.impressions ?? 0) > 0) done.add('measure')

  return { done }
}
