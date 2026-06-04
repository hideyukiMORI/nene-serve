import { setupServer } from 'msw/node'
import { authHandlers } from './handlers/auth'
import { creativeHandlers } from './handlers/creatives'
import { invitationHandlers } from './handlers/invitations'
import { marketplaceHandlers } from './handlers/marketplace'
import { metricsHandlers } from './handlers/metrics'
import { placementHandlers } from './handlers/placements'
import { settingsHandlers } from './handlers/settings'
import { userHandlers } from './handlers/users'

export const mswServer = setupServer(
  ...authHandlers,
  ...placementHandlers,
  ...creativeHandlers,
  ...metricsHandlers,
  ...marketplaceHandlers,
  ...userHandlers,
  ...settingsHandlers,
  ...invitationHandlers,
)
