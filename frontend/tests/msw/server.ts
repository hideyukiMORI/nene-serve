import { setupServer } from 'msw/node'
import { authHandlers } from './handlers/auth'
import { placementHandlers } from './handlers/placements'

export const mswServer = setupServer(...authHandlers, ...placementHandlers)
