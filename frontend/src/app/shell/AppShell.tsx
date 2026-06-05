import type { ComponentType, SVGProps } from 'react'
import { Outlet, useLocation, useNavigate } from 'react-router-dom'
import { useCurrentUser } from '@/entities/auth'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import {
  IconBoard,
  IconHome,
  IconInvoice,
  IconLogo,
  IconSettings,
  IconShield,
  IconStages,
  IconUsers,
} from '@/shared/ui/icons'
import { LangToggle, SignoutButton, ThemeToggle } from './Toggles'

interface NavItem {
  to: string
  label: MessageKey
  Icon: ComponentType<SVGProps<SVGSVGElement>>
}
interface NavGroup {
  label: MessageKey
  items: NavItem[]
}

const NAV: NavGroup[] = [
  {
    label: 'nav.group.prepare',
    items: [{ to: '/', label: 'nav.home', Icon: IconHome }],
  },
  {
    label: 'nav.group.build',
    items: [
      { to: '/placements', label: 'nav.placements', Icon: IconBoard },
      { to: '/creatives', label: 'nav.creatives', Icon: IconStages },
    ],
  },
  { label: 'nav.group.govern', items: [{ to: '/review', label: 'nav.review', Icon: IconShield }] },
  {
    label: 'nav.group.measure',
    items: [{ to: '/metrics', label: 'nav.metrics', Icon: IconStages }],
  },
  {
    label: 'nav.group.business',
    items: [{ to: '/marketplace', label: 'nav.marketplace', Icon: IconInvoice }],
  },
  {
    label: 'nav.group.admin',
    items: [
      { to: '/users', label: 'nav.users', Icon: IconUsers },
      { to: '/settings', label: 'nav.settings', Icon: IconSettings },
    ],
  },
]

function isActive(to: string, pathname: string): boolean {
  return to === '/' ? pathname === '/' : pathname.startsWith(to)
}

/** Control Room shell: a grouped sidebar + a topbar, with the active route in a scroll area. */
export function AppShell() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const location = useLocation()
  const currentUser = useCurrentUser()
  const email = currentUser.data?.email ?? ''
  const initials = (email !== '' ? email.slice(0, 2) : 'NS').toUpperCase()

  return (
    <div className="app">
      <aside className="sidebar">
        <div className="side-brand">
          <span className="logo-mark">
            <IconLogo />
          </span>
          <div className="stack">
            <span className="brand-name">NeNe Serve</span>
            <span className="brand-sub">{t('app.subtitle')}</span>
          </div>
        </div>
        <div className="side-scroll">
          {NAV.map((group) => (
            <div className="nav-group" key={group.label}>
              <div className="nav-group-label">{t(group.label)}</div>
              {group.items.map((item) => (
                <button
                  key={item.to}
                  type="button"
                  className={`nav-item ${isActive(item.to, location.pathname) ? 'active' : ''}`}
                  onClick={() => {
                    void navigate(item.to)
                  }}
                >
                  <span className="ni-icon">
                    <item.Icon />
                  </span>
                  {t(item.label)}
                </button>
              ))}
            </div>
          ))}
        </div>
      </aside>

      <div className="content-col">
        <header className="topbar">
          <div className="row g2" style={{ marginLeft: 'auto' }}>
            <ThemeToggle />
            <LangToggle />
            <span className="vr" style={{ height: 24 }} />
            {email !== '' ? (
              <div className="row g2">
                <span className="avatar">{initials}</span>
                <div className="stack" style={{ lineHeight: 1.1 }}>
                  <span className="t-cap" style={{ fontWeight: 600 }}>
                    {email}
                  </span>
                  <span className="t-tiny faint">{currentUser.data?.role ?? ''}</span>
                </div>
              </div>
            ) : null}
            <SignoutButton />
          </div>
        </header>
        <div className="scroll-area">
          <Outlet />
        </div>
      </div>
    </div>
  )
}
