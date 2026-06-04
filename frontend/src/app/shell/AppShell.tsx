import type { ComponentType, SVGProps } from 'react'
import { Outlet, useLocation, useNavigate } from 'react-router-dom'
import { useCurrentUser } from '@/entities/auth'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { IconBoard, IconInvoice, IconLogo, IconShield, IconStages } from '@/shared/ui/icons'
import { LangToggle, SignoutButton, ThemeToggle } from './Toggles'

interface NavEntry {
  to: string
  label: MessageKey
  Icon: ComponentType<SVGProps<SVGSVGElement>>
}

const NAV: NavEntry[] = [
  { to: '/', label: 'nav.placements', Icon: IconBoard },
  { to: '/creatives', label: 'nav.creatives', Icon: IconBoard },
  { to: '/review', label: 'nav.review', Icon: IconShield },
  { to: '/metrics', label: 'nav.metrics', Icon: IconStages },
  { to: '/marketplace', label: 'nav.marketplace', Icon: IconInvoice },
]

function isActive(to: string, pathname: string): boolean {
  return to === '/' ? pathname === '/' : pathname.startsWith(to)
}

/**
 * Admin app shell: brand + primary nav + locale/theme/sign-out controls, with
 * the active route rendered through <Outlet/>. Wraps authenticated routes.
 */
export function AppShell() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const location = useLocation()
  const currentUser = useCurrentUser()
  const email = currentUser.data?.email ?? ''
  const avatarLetter = (email[0] ?? 'N').toUpperCase()

  return (
    <div className="shell shell-calm">
      <header className="topnav">
        <div className="row g3">
          <div className="brandmark">
            <span className="logo">
              <IconLogo />
            </span>
            <div className="stack" style={{ lineHeight: 1.12 }}>
              <span className="name">{t('app.title')}</span>
              <span className="sub">{t('app.subtitle')}</span>
            </div>
          </div>
        </div>
        <nav className="topnav-links">
          {NAV.map((entry) => (
            <button
              key={entry.to}
              type="button"
              className={`topnav-link ${isActive(entry.to, location.pathname) ? 'active' : ''}`}
              onClick={() => {
                void navigate(entry.to)
              }}
            >
              <entry.Icon />
              {t(entry.label)}
            </button>
          ))}
        </nav>
        <div className="topnav-right">
          <span className="hdr-controls">
            <ThemeToggle />
            <LangToggle />
          </span>
          <span className="nav-divider" />
          {email !== '' ? (
            <span className="account-chip">
              <span className="avatar">{avatarLetter}</span>
            </span>
          ) : null}
          <SignoutButton />
        </div>
      </header>

      <div className="main">
        <div className="main-inner">
          <Outlet />
        </div>
      </div>
    </div>
  )
}
