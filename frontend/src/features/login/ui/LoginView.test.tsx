import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, expect, it, vi } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { LoginView } from './LoginView'

describe('LoginView', () => {
  it('renders the email and password fields', () => {
    renderWithProviders(
      <LoginView pending={false} errorMessage={null} onSubmit={() => Promise.resolve(true)} />,
    )

    expect(screen.getByRole('heading', { name: 'Sign in' })).toBeInTheDocument()
    expect(screen.getByLabelText('Email')).toBeInTheDocument()
    expect(screen.getByLabelText('Password')).toBeInTheDocument()
  })

  it('submits entered credentials', async () => {
    const user = userEvent.setup()
    const onSubmit = vi.fn(() => Promise.resolve(true))
    renderWithProviders(<LoginView pending={false} errorMessage={null} onSubmit={onSubmit} />)

    await user.type(screen.getByLabelText('Organization'), 'acme')
    await user.type(screen.getByLabelText('Email'), 'admin@acme.test')
    await user.type(screen.getByLabelText('Password'), 'password')
    await user.click(screen.getByRole('button', { name: 'Sign in' }))

    expect(onSubmit).toHaveBeenCalledWith({
      organization: 'acme',
      email: 'admin@acme.test',
      password: 'password',
    })
  })

  it('replaces the org field with the resolved tenant and submits its slug', async () => {
    const user = userEvent.setup()
    const onSubmit = vi.fn(() => Promise.resolve(true))
    renderWithProviders(
      <LoginView
        pending={false}
        errorMessage={null}
        tenant={{ slug: 'acme', name: 'Acme' }}
        onSubmit={onSubmit}
      />,
    )

    expect(screen.queryByLabelText('Organization')).not.toBeInTheDocument()
    expect(screen.getByText('Signing in to Acme')).toBeInTheDocument()

    await user.type(screen.getByLabelText('Email'), 'admin@acme.test')
    await user.type(screen.getByLabelText('Password'), 'password')
    await user.click(screen.getByRole('button', { name: 'Sign in' }))

    expect(onSubmit).toHaveBeenCalledWith({
      organization: 'acme',
      email: 'admin@acme.test',
      password: 'password',
    })
  })

  it('shows an error message', () => {
    renderWithProviders(
      <LoginView
        pending={false}
        errorMessage="Invalid email or password."
        onSubmit={() => Promise.resolve(false)}
      />,
    )

    expect(screen.getByText('Invalid email or password.')).toBeInTheDocument()
  })
})
