import { screen, waitFor } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { PlacementsPage } from './PlacementsPage'

describe('PlacementsPage', () => {
  it('renders the placements returned by the API', async () => {
    renderWithProviders(<PlacementsPage />)

    await waitFor(() => {
      expect(screen.getByText('pk_acme_home')).toBeInTheDocument()
    })
    expect(screen.getByText('pk_acme_side')).toBeInTheDocument()
    // The side placement has no default creative → em dash fallback.
    expect(screen.getByText('—')).toBeInTheDocument()
  })

  it('shows the create-placement form', async () => {
    renderWithProviders(<PlacementsPage />)
    await waitFor(() => {
      expect(screen.getByText('New placement')).toBeInTheDocument()
    })
  })
})
