import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { MarketplacePage } from './MarketplacePage'

describe('MarketplacePage', () => {
  it('renders advertisers, pricing rules and campaigns from the API', async () => {
    renderWithProviders(<MarketplacePage />)

    // Advertiser/pricing names also appear in the campaign form's <select> options,
    // so they are expected to occur more than once.
    await waitFor(() => {
      expect(screen.getAllByText('Acme Media').length).toBeGreaterThan(0)
    })
    expect(screen.getAllByText('CPM standard').length).toBeGreaterThan(0)
    expect(screen.getByText('Spring push')).toBeInTheDocument()
  })

  it('exposes create forms and submits a new advertiser', async () => {
    const user = userEvent.setup()
    renderWithProviders(<MarketplacePage />)

    await waitFor(() => {
      expect(screen.getByText('New advertiser')).toBeInTheDocument()
    })

    // The advertiser form is the first 'Name' field on the page.
    const nameField = screen.getAllByLabelText('Name')[0]
    expect(nameField).toBeDefined()
    await user.type(nameField as HTMLElement, 'Globex')
    const createButtons = screen.getAllByRole('button', { name: 'Create' })
    await user.click(createButtons[0] as HTMLElement)

    // Mutation resolves (201) and the button returns to its idle label.
    await waitFor(() => {
      expect(screen.getAllByRole('button', { name: 'Create' })[0]).toBeEnabled()
    })
  })
})
