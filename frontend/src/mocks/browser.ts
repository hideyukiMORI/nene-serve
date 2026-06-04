import { setupWorker } from 'msw/browser'
import { authHandlers } from '../../tests/msw/handlers/auth'
import { creativeHandlers } from '../../tests/msw/handlers/creatives'
import { metricsHandlers } from '../../tests/msw/handlers/metrics'
import { placementHandlers } from '../../tests/msw/handlers/placements'

export const worker = setupWorker(
  ...authHandlers,
  ...placementHandlers,
  ...creativeHandlers,
  ...metricsHandlers,
)
