export type PillTone = 'neutral' | 'pending' | 'go' | 'stop' | 'info'

const REVIEW_TONE: Record<string, PillTone> = {
  approved: 'go',
  draft: 'neutral',
  submitted: 'pending',
  in_review: 'pending',
  rejected: 'stop',
  changes_requested: 'info',
}

export function reviewStatusTone(status: string): PillTone {
  return REVIEW_TONE[status] ?? 'neutral'
}

export function lifecycleTone(status: string): PillTone {
  if (status === 'active' || status === 'funded') return 'go'
  return 'neutral'
}
