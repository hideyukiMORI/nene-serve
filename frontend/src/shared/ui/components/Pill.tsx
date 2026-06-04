import type { PillTone } from './status-tone'

export interface PillProps {
  tone: PillTone
  children: React.ReactNode
}

/** Status pill (colored chip + dot). */
export function Pill({ tone, children }: PillProps) {
  return (
    <span className={`pill pill-${tone}`}>
      <span className="pdot" />
      {children}
    </span>
  )
}
