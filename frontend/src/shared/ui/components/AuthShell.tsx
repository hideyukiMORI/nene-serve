import type { ReactNode } from 'react'
import { useTranslation } from '@/shared/i18n'
import { IconLogo } from '@/shared/ui/icons'

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
    <div
      className="auth-grid"
      style={{
        display: 'grid',
        gridTemplateColumns: 'minmax(0,1fr) minmax(0,1fr)',
        minHeight: '100vh',
      }}
    >
      <aside
        style={{
          position: 'relative',
          background: 'var(--accent)',
          color: 'var(--ink-on-accent)',
          padding: '40px',
          display: 'flex',
          flexDirection: 'column',
          justifyContent: 'space-between',
          overflow: 'hidden',
        }}
      >
        <div style={{ color: 'var(--ink-on-accent)' }}>
          <SignalDeco />
        </div>
        <div className="row g2" style={{ position: 'relative' }}>
          <span
            style={{
              width: 34,
              height: 34,
              borderRadius: 9,
              background: 'rgba(255,255,255,.16)',
              display: 'grid',
              placeItems: 'center',
            }}
          >
            <IconLogo />
          </span>
          <span style={{ fontWeight: 600, fontSize: 16 }}>NeNe Serve</span>
        </div>
        <div className="stack g3" style={{ position: 'relative', maxWidth: '30ch' }}>
          <h2 className="t-display" style={{ color: 'var(--ink-on-accent)' }}>
            {t('app.subtitle')}
          </h2>
          <p style={{ opacity: 0.85, fontSize: 14, lineHeight: 1.6 }}>{t('login.secure')}</p>
        </div>
        <span className="t-tiny" style={{ position: 'relative', opacity: 0.7 }}>
          © 2026 NeNe Serve
        </span>
      </aside>
      <div
        style={{
          display: 'grid',
          placeItems: 'center',
          padding: '40px 28px',
          background: 'var(--bg)',
        }}
      >
        <div style={{ width: 'min(380px, 100%)' }}>{children}</div>
      </div>
    </div>
  )
}
