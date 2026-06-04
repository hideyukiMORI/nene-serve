import { screen, waitFor } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderWithProviders } from '@tests/render/render-with-providers'
import { MetricsPage } from './MetricsPage'

describe('MetricsPage', () => {
  it('renders KPI totals and the daily rows', async () => {
    renderWithProviders(<MetricsPage />)

    // Overall CTR = 130 / 3000 = 4.33% (toFixed → locale-independent).
    await waitFor(() => {
      expect(screen.getByText('4.33%')).toBeInTheDocument()
    })
    // Overall fill rate = 3000 / 3600 = 83.33%.
    expect(screen.getByText('83.33%')).toBeInTheDocument()
    // Daily rows present.
    expect(screen.getByText('2026-06-03')).toBeInTheDocument()
    expect(screen.getByText('2026-06-04')).toBeInTheDocument()
  })
})
