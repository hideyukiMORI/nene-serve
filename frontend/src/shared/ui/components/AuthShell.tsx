import type { ReactNode } from 'react'
import { useTranslation } from '@/shared/i18n'
import { IconBeacon } from '@/shared/ui/icons'

function SignalDeco() {
  return (
    <svg
      className="signal-deco"
      viewBox="0 0 400 600"
      preserveAspectRatio="xMidYMid slice"
      aria-hidden="true"
    >
      {[60, 130, 200, 270, 340, 410].map((r, i) => (
        <circle
          key={r}
          cx="60"
          cy="300"
          r={r}
          fill="none"
          stroke="currentColor"
          strokeWidth="1.2"
          opacity={0.5 - i * 0.06}
        />
      ))}
      <circle cx="60" cy="300" r="9" fill="currentColor" opacity="0.8" />
    </svg>
  )
}

/** Two-column auth frame: accent brand panel + the form. */
export function AuthShell({ children }: { children: ReactNode }) {
  const { t } = useTranslation()
  return (
    <div className="auth-grid">
      <aside className="auth-aside">
        <SignalDeco />
        <div className="auth-brand">
          <span className="auth-brand-mark">
            <IconBeacon />
          </span>
          <span className="auth-brand-name">NeNe Serve</span>
        </div>
        <div className="auth-pitch stack g3">
          <h2 className="t-display">{t('app.subtitle')}</h2>
          <p>{t('login.secure')}</p>
        </div>
        <span className="auth-copy t-tiny">© 2026 NeNe Serve</span>
      </aside>
      <div className="auth-form-col">
        <div className="auth-form">{children}</div>
      </div>
    </div>
  )
}
