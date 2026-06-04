import { screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { UsersPage } from './UsersPage'

describe('UsersPage', () => {
  it('lists users and exposes the invite form', async () => {
    renderWithProviders(<UsersPage />)
    await waitFor(() => {
      expect(screen.getByText('admin@acme.test')).toBeInTheDocument()
    })
    expect(screen.getByText('Invite a user')).toBeInTheDocument()
  })

  it('invites a user and confirms the email was sent', async () => {
    const user = userEvent.setup()
    renderWithProviders(<UsersPage />)
    await waitFor(() => {
      expect(screen.getByText('Invite a user')).toBeInTheDocument()
    })
    await user.type(screen.getByLabelText('Email'), 'newbie@acme.test')
    await user.click(screen.getByRole('button', { name: 'Send invite' }))
    await waitFor(() => {
      expect(screen.getByText('Invitation email sent.')).toBeInTheDocument()
    })
  })
})
