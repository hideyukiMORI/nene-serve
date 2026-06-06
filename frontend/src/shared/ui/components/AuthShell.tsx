import { useEffect, useRef, type ReactNode } from 'react'
import { useTranslation } from '@/shared/i18n'
import { IconBeacon } from '@/shared/ui/icons'

/**
 * Pointer parallax for the signal deco: maps the cursor position over the panel
 * to `--px`/`--py` (−1..1), which the rings translate by per depth (serve.css).
 * No-op under prefers-reduced-motion.
 */
function useSignalParallax() {
  const ref = useRef<HTMLElement>(null)

  useEffect(() => {
    const el = ref.current
    const reduceMotion =
      typeof window.matchMedia === 'function' &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches
    if (el === null || reduceMotion) {
      return
    }

    const onMove = (event: PointerEvent): void => {
      const rect = el.getBoundingClientRect()
      const px = ((event.clientX - rect.left) / rect.width) * 2 - 1
      const py = ((event.clientY - rect.top) / rect.height) * 2 - 1
      el.style.setProperty('--px', px.toFixed(3))
      el.style.setProperty('--py', py.toFixed(3))
    }
    const reset = (): void => {
      el.style.setProperty('--px', '0')
      el.style.setProperty('--py', '0')
    }

    el.addEventListener('pointermove', onMove)
    el.addEventListener('pointerleave', reset)
    return () => {
      el.removeEventListener('pointermove', onMove)
      el.removeEventListener('pointerleave', reset)
    }
  }, [])

  return ref
}

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
  const asideRef = useSignalParallax()
  return (
    <div className="auth-grid">
      <aside className="auth-aside" ref={asideRef}>
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
