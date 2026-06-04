import { setupServer } from 'msw/node'
import { authHandlers } from './handlers/auth'
import { creativeHandlers } from './handlers/creatives'
import { metricsHandlers } from './handlers/metrics'
import { placementHandlers } from './handlers/placements'

export const mswServer = setupServer(
  ...authHandlers,
  ...placementHandlers,
  ...creativeHandlers,
  ...metricsHandlers,
)
