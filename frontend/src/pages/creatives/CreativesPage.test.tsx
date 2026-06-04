import { screen, waitFor } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { CreativesPage } from './CreativesPage'

describe('CreativesPage', () => {
  it('renders the creatives returned by the API', async () => {
    renderWithProviders(<CreativesPage />)
    await waitFor(() => {
      expect(screen.getByText('cr-acme-banner')).toBeInTheDocument()
    })
    expect(screen.getByText('cr-acme-draft')).toBeInTheDocument()
  })

  it('shows the create-image-creative form', async () => {
    renderWithProviders(<CreativesPage />)
    await waitFor(() => {
      expect(screen.getByText('New image creative')).toBeInTheDocument()
    })
  })
})
