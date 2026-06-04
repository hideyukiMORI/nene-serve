import { screen, waitFor } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { SettingsPage } from './SettingsPage'

describe('SettingsPage', () => {
  it('loads the stored SMTP settings into the form', async () => {
    renderWithProviders(<SettingsPage />)
    await waitFor(() => {
      expect(screen.getByLabelText('Host')).toHaveValue('mailpit')
    })
    expect(screen.getByRole('button', { name: 'Send test email' })).toBeEnabled()
  })
})
