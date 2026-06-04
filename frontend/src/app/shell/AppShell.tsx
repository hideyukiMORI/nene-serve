import { Outlet, useNavigate } from 'react-router-dom'
import { useCurrentUser } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { IconBoard, IconLogo } from '@/shared/ui/icons'
import { LangToggle, SignoutButton, ThemeToggle } from './Toggles'

/**
 * Admin app shell: brand + primary nav + locale/theme/sign-out controls, with
 * the active route rendered through <Outlet/>. Wraps authenticated routes.
 */
export function AppShell() {
  const { t } = useTranslation()
  const navigate = useNavigate()
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
          <button
            type="button"
            className="topnav-link active"
            onClick={() => {
              void navigate('/')
            }}
          >
            <IconBoard />
            {t('nav.placements')}
          </button>
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
