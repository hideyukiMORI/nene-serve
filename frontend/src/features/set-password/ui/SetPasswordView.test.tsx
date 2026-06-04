import { screen } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { SetPasswordView } from './SetPasswordView'

describe('SetPasswordView', () => {
  it('shows the form with the invitee email when valid', () => {
    renderWithProviders(
      <SetPasswordView
        validating={false}
        email="invitee@acme.test"
        invalid={false}
        submitting={false}
        errorMessage={null}
        onSubmit={() => Promise.resolve(true)}
      />,
    )
    expect(screen.getByText('invitee@acme.test')).toBeInTheDocument()
    expect(screen.getByLabelText('New password')).toBeInTheDocument()
  })

  it('shows an invalid message for a bad token', () => {
    renderWithProviders(
      <SetPasswordView
        validating={false}
        email={null}
        invalid={true}
        submitting={false}
        errorMessage={null}
        onSubmit={() => Promise.resolve(false)}
      />,
    )
    expect(screen.getByText('This invitation is invalid, used, or expired.')).toBeInTheDocument()
  })
})
