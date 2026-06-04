import { setupWorker } from 'msw/browser'
import { authHandlers } from '../../tests/msw/handlers/auth'
import { creativeHandlers } from '../../tests/msw/handlers/creatives'
import { placementHandlers } from '../../tests/msw/handlers/placements'

export const worker = setupWorker(...authHandlers, ...placementHandlers, ...creativeHandlers)
