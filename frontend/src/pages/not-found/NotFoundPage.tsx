import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'

export function NotFoundPage() {
  const { t } = useTranslation()

  return (
    <div style={{ display: 'grid', placeItems: 'center', minHeight: '70vh', padding: 24 }}>
      <section className="stack g4" style={{ textAlign: 'center', alignItems: 'center' }}>
        <span className="t-display mono faint">404</span>
        <h1 className="t-h1">{t('notFound.title')}</h1>
        <p className="muted t-body" style={{ maxWidth: '44ch' }}>
          {t('notFound.body')}
        </p>
        <Link className="btn btn-primary" to="/">
          {t('notFound.back')}
        </Link>
      </section>
    </div>
  )
}
