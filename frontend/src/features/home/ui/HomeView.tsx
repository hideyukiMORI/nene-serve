import { Fragment, type ComponentType, type CSSProperties, type SVGProps } from 'react'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { Pill } from '@/shared/ui'
import {
  IconArrowRight,
  IconBoard,
  IconCheck,
  IconImage,
  IconLink,
  IconMail,
  IconShield,
  IconSparkle,
  IconStages,
  IconUsers,
} from '@/shared/ui/icons'
import type { HomeStepId } from '../model/use-home-page'

interface StepDef {
  id: HomeStepId
  to: string
  Icon: ComponentType<SVGProps<SVGSVGElement>>
  titleKey: MessageKey
  descKey: MessageKey
}

/** The seven steps of the core serve loop, in the order they appear. */
const STEPS: StepDef[] = [
  { id: 'smtp', to: '/settings', Icon: IconMail, titleKey: 'home.steps.smtp.title', descKey: 'home.steps.smtp.desc' }, // prettier-ignore
  { id: 'invite', to: '/users', Icon: IconUsers, titleKey: 'home.steps.invite.title', descKey: 'home.steps.invite.desc' }, // prettier-ignore
  { id: 'placement', to: '/placements', Icon: IconStages, titleKey: 'home.steps.placement.title', descKey: 'home.steps.placement.desc' }, // prettier-ignore
  { id: 'creative', to: '/creatives', Icon: IconImage, titleKey: 'home.steps.creative.title', descKey: 'home.steps.creative.desc' }, // prettier-ignore
  { id: 'approve', to: '/review', Icon: IconShield, titleKey: 'home.steps.approve.title', descKey: 'home.steps.approve.desc' }, // prettier-ignore
  { id: 'embed', to: '/placements', Icon: IconLink, titleKey: 'home.steps.embed.title', descKey: 'home.steps.embed.desc' }, // prettier-ignore
  { id: 'measure', to: '/metrics', Icon: IconBoard, titleKey: 'home.steps.measure.title', descKey: 'home.steps.measure.desc' }, // prettier-ignore
]

export interface HomeViewProps {
  done: ReadonlySet<HomeStepId>
  onNavigate: (to: string) => void
}

export function HomeView({ done, onNavigate }: HomeViewProps) {
  const { t } = useTranslation()

  const total = STEPS.length
  const doneCount = STEPS.filter((step) => done.has(step.id)).length
  const pct = Math.round((doneCount / total) * 100)
  const current = STEPS.find((step) => !done.has(step.id))

  return (
    <div className="page fade-up">
      <div className="stack g5">
        <div className="stack g2">
          <span className="eyebrow">{t('nav.home')}</span>
          <h1 className="t-display">{t('home.greeting')}</h1>
          <p className="t-body muted home-lead">{t('home.lead')}</p>
        </div>

        <div className="card card-pad-lg setup-card stack g4">
          <div className="between wrap g3">
            <div className="stack g1">
              <div className="row g2">
                <span className="t-h2">{t('home.setup.title')}</span>
                <span className="chip">
                  <IconSparkle />
                  {t('home.setup.progress', { done: doneCount, total })}
                </span>
              </div>
              <span className="t-cap muted">{t('home.setup.subtitle')}</span>
            </div>
            {current !== undefined ? (
              <button
                type="button"
                className="btn btn-primary btn-sm"
                onClick={() => {
                  onNavigate(current.to)
                }}
              >
                {t('home.setup.resume')}
                <IconArrowRight />
              </button>
            ) : null}
          </div>

          <div className="setup-prog" style={{ '--pct': `${String(pct)}%` } as CSSProperties}>
            <i />
          </div>

          <div className="loop">
            {STEPS.map((step, index) => {
              const isDone = done.has(step.id)
              const isCurrent = step.id === current?.id
              const state = isDone ? 'is-done' : isCurrent ? 'is-current' : ''
              return (
                <Fragment key={step.id}>
                  <button
                    type="button"
                    className={`loop-step ${state}`}
                    title={t(step.titleKey)}
                    onClick={() => {
                      onNavigate(step.to)
                    }}
                  >
                    <span className="loop-mark">{isDone ? <IconCheck /> : <step.Icon />}</span>
                    <span className="loop-label">{t(step.titleKey)}</span>
                  </button>
                  {index < STEPS.length - 1 ? (
                    <span className="loop-bar" aria-hidden="true" />
                  ) : null}
                </Fragment>
              )
            })}
          </div>
        </div>

        <div className="grid-cards grid-steps">
          {STEPS.map((step, index) => {
            const isDone = done.has(step.id)
            const isCurrent = step.id === current?.id
            const state = `${isDone ? 'done' : ''} ${isCurrent ? 'current' : ''}`.trim()
            return (
              <button
                type="button"
                key={step.id}
                className={`step clickable ${state}`}
                onClick={() => {
                  onNavigate(step.to)
                }}
              >
                <span className="step-check">{isDone ? <IconCheck /> : index + 1}</span>
                <div className="stack g1 grow">
                  <div className="between g2">
                    <span className="t-h3">{t(step.titleKey)}</span>
                    {isDone ? (
                      <Pill tone="go">{t('home.step.done')}</Pill>
                    ) : isCurrent ? (
                      <span className="chip chip-accent">{t('home.step.doThis')}</span>
                    ) : null}
                  </div>
                  <span className="t-cap muted">{t(step.descKey)}</span>
                </div>
                <span className="step-arrow">
                  <IconArrowRight />
                </span>
              </button>
            )
          })}
        </div>
      </div>
    </div>
  )
}
