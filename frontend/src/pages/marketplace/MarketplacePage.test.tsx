import { screen, waitFor } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { MarketplacePage } from './MarketplacePage'

describe('MarketplacePage', () => {
  it('renders advertisers, pricing rules and campaigns from the API', async () => {
    renderWithProviders(<MarketplacePage />)

    await waitFor(() => {
      expect(screen.getByText('Acme Media')).toBeInTheDocument()
    })
    expect(screen.getByText('CPM standard')).toBeInTheDocument()
    expect(screen.getByText('Spring push')).toBeInTheDocument()
  })
})
