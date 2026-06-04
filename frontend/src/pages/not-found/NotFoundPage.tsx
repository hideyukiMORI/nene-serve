import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'

export function NotFoundPage() {
  const { t } = useTranslation()

  return (
    <div className="fs-wrap">
      <section className="notfound stack g4" style={{ alignContent: 'center' }}>
        <span className="code">404</span>
        <h1 className="t-h1">{t('notFound.title')}</h1>
        <p className="muted t-body" style={{ maxWidth: '44ch' }}>
          {t('notFound.body')}
        </p>
        <div className="row g3" style={{ justifyContent: 'center', marginTop: 8 }}>
          <Link className="btn btn-primary" to="/">
            {t('notFound.back')}
          </Link>
        </div>
      </section>
    </div>
  )
}
