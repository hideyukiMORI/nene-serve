import type { ReactNode } from 'react'

export interface PageProps {
  title: string
  subtitle?: string
  actions?: ReactNode
  wide?: boolean
  children: ReactNode
}

/** Standard page frame: `.page` + a `.page-head` (title/subtitle + optional actions). */
export function Page({ title, subtitle, actions, wide = false, children }: PageProps) {
  return (
    <div className={`page${wide ? ' page-wide' : ''}`}>
      <div className="page-head">
        <div className="between">
          <div className="stack g1">
            <h1 className="t-h1">{title}</h1>
            {subtitle !== undefined ? <span className="muted t-cap">{subtitle}</span> : null}
          </div>
          {actions !== undefined ? <div className="row g2">{actions}</div> : null}
        </div>
      </div>
      {children}
    </div>
  )
}
