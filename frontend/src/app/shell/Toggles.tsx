import { useNavigate } from 'react-router-dom'
import { authStore } from '@/shared/auth'
import { LOCALES, SUPPORTED_LOCALE_IDS, useTranslation } from '@/shared/i18n'
import { useTheme } from '@/shared/theme'
import { IconArrowOut, IconMoon, IconSun } from '@/shared/ui/icons'

/** Theme + language segmented toggles ("Control Room" .seg style). */
export function ThemeToggle() {
  const { t } = useTranslation()
  const { theme, setTheme } = useTheme()
  return (
    <div className="seg" title={t('shell.theme')}>
      <button
        type="button"
        className={theme === 'light' ? 'active' : ''}
        aria-label={t('shell.themeLight')}
        aria-pressed={theme === 'light'}
        onClick={() => {
          setTheme('light')
        }}
      >
        <IconSun />
      </button>
      <button
        type="button"
        className={theme === 'dark' ? 'active' : ''}
        aria-label={t('shell.themeDark')}
        aria-pressed={theme === 'dark'}
        onClick={() => {
          setTheme('dark')
        }}
      >
        <IconMoon />
      </button>
    </div>
  )
}

export function LangToggle() {
  const { t, locale, setLocale } = useTranslation()
  return (
    <div className="seg" title={t('shell.lang')}>
      {SUPPORTED_LOCALE_IDS.map((id) => (
        <button
          key={id}
          type="button"
          className={locale === id ? 'active' : ''}
          aria-pressed={locale === id}
          onClick={() => {
            setLocale(id)
          }}
        >
          {id === 'en' ? 'EN' : LOCALES[id].label}
        </button>
      ))}
    </div>
  )
}

export function SignoutButton() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  return (
    <button
      type="button"
      className="icon-btn"
      aria-label={t('shell.signout')}
      title={t('shell.signout')}
      onClick={() => {
        authStore.clear()
        void navigate('/login')
      }}
    >
      <IconArrowOut />
    </button>
  )
}
