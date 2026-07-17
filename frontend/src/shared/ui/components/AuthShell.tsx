import { useEffect, useRef, type ReactNode } from 'react'
import { useTranslation } from '@/shared/i18n'
import { IconBeacon } from '@/shared/ui/icons'

/**
 * Animated login "signal deco" (Claude Design login-left handoff): a layered SVG
 * — a faint static backdrop, expanding sonar ripples, and a pulsing emitter core
 * — with a requestAnimationFrame loop that parallaxes each layer by its depth
 * (mouse-follow, eased, plus a slow autonomous sin/cos drift). The host `<aside>`
 * is position:relative / overflow:hidden and sets `color`, so `currentColor`
 * drives the line colour. Decoration only (`aria-hidden`); respects reduced motion.
 */
function SignalDeco() {
  const ref = useRef<SVGSVGElement>(null)

  useEffect(() => {
    const svg = ref.current
    if (svg === null) {
      return
    }
    const host = svg.closest('aside') ?? svg.parentElement
    if (host === null) {
      return
    }
    const layers = [...svg.querySelectorAll<SVGGElement>('.deco-layer')]
    const reduce =
      typeof window.matchMedia === 'function' &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches

    let tx = 0
    let ty = 0
    let cx = 0
    let cy = 0
    let raf = 0

    const onMove = (event: PointerEvent): void => {
      const r = host.getBoundingClientRect()
      tx = ((event.clientX - r.left) / r.width - 0.5) * 2 // -1..1
      ty = ((event.clientY - r.top) / r.height - 0.5) * 2
    }
    const onLeave = (): void => {
      tx = 0
      ty = 0
    }

    host.addEventListener('pointermove', onMove)
    host.addEventListener('pointerleave', onLeave)

    const t0 = performance.now()
    const tick = (now: number): void => {
      const t = (now - t0) / 1000
      cx += (tx - cx) * 0.05
      cy += (ty - cy) * 0.05
      const driftX = reduce ? 0 : Math.sin(t * 0.24)
      const driftY = reduce ? 0 : Math.cos(t * 0.17)
      for (const layer of layers) {
        const depth = Number.parseFloat(layer.dataset.depth ?? '1') || 1
        const px = (cx * 30 + driftX * 8) * depth
        const py = (cy * 30 + driftY * 6) * depth
        layer.style.transform = `translate(${px.toFixed(2)}px, ${py.toFixed(2)}px)`
      }
      raf = requestAnimationFrame(tick)
    }
    raf = requestAnimationFrame(tick)

    return () => {
      cancelAnimationFrame(raf)
      host.removeEventListener('pointermove', onMove)
      host.removeEventListener('pointerleave', onLeave)
    }
  }, [])

  return (
    <svg
      ref={ref}
      className="signal-deco"
      viewBox="0 0 400 600"
      preserveAspectRatio="xMidYMid slice"
      aria-hidden="true"
    >
      {/* Layer 1 — static backdrop rings */}
      <g className="deco-layer deco-static" data-depth="0.3">
        {[90, 165, 245, 330, 420, 515].map((r) => (
          <circle
            key={r}
            cx="60"
            cy="300"
            r={r}
            fill="none"
            stroke="currentColor"
            strokeWidth="1"
          />
        ))}
      </g>
      {/* Layer 2 — sonar ripples */}
      <g className="deco-layer" data-depth="0.85">
        {[0, 1, 2, 3, 4].map((i) => (
          <circle
            key={i}
            className="ripple"
            cx="60"
            cy="300"
            r="44"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.4"
            vectorEffect="non-scaling-stroke"
            style={{ animationDelay: `${String(i * 1.7)}s` }}
          />
        ))}
      </g>
      {/* Layer 3 — emitter core + halo */}
      <g className="deco-layer" data-depth="1.6">
        <circle
          className="core-halo"
          cx="60"
          cy="300"
          r="9"
          fill="none"
          stroke="currentColor"
          strokeWidth="1.6"
        />
        <circle className="core-dot" cx="60" cy="300" r="9" fill="currentColor" />
      </g>
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
          <span className="auth-brand-name">
            <b>NeNe</b>
            <span> Serve</span>
          </span>
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
