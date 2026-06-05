import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { ReviewPage } from './ReviewPage'

describe('ReviewPage', () => {
  it('lists the review queue and exposes review actions', async () => {
    renderWithProviders(<ReviewPage />)
    await waitFor(() => {
      expect(screen.getByText('cr-acme-pending')).toBeInTheDocument()
    })
    // The queue holds several items, so there are multiple Approve buttons.
    expect(screen.getAllByRole('button', { name: 'Approve' })[0]).toBeInTheDocument()
  })

  it('runs a review action without error', async () => {
    const user = userEvent.setup()
    renderWithProviders(<ReviewPage />)
    await waitFor(() => {
      expect(screen.getByText('cr-acme-pending')).toBeInTheDocument()
    })
    await user.click(screen.getAllByRole('button', { name: 'Approve' })[0] as HTMLElement)
    // The row stays rendered; the mutation resolves and invalidates the query.
    await waitFor(() => {
      expect(screen.getAllByRole('button', { name: 'Approve' })[0]).toBeEnabled()
    })
  })
})
