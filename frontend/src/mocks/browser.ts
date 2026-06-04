import { setupWorker } from 'msw/browser'
import { authHandlers } from '../../tests/msw/handlers/auth'
import { creativeHandlers } from '../../tests/msw/handlers/creatives'
import { invitationHandlers } from '../../tests/msw/handlers/invitations'
import { marketplaceHandlers } from '../../tests/msw/handlers/marketplace'
import { metricsHandlers } from '../../tests/msw/handlers/metrics'
import { placementHandlers } from '../../tests/msw/handlers/placements'
import { settingsHandlers } from '../../tests/msw/handlers/settings'
import { userHandlers } from '../../tests/msw/handlers/users'

export const worker = setupWorker(
  ...authHandlers,
  ...placementHandlers,
  ...creativeHandlers,
  ...metricsHandlers,
  ...marketplaceHandlers,
  ...userHandlers,
  ...settingsHandlers,
  ...invitationHandlers,
)
