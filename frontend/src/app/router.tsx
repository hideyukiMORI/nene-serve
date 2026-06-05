import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import { CreativesPage } from '@/pages/creatives/CreativesPage'
import { HomePage } from '@/pages/home/HomePage'
import { LoginPage } from '@/pages/login/LoginPage'
import { MarketplacePage } from '@/pages/marketplace/MarketplacePage'
import { MetricsPage } from '@/pages/metrics/MetricsPage'
import { NotFoundPage } from '@/pages/not-found/NotFoundPage'
import { PlacementsPage } from '@/pages/placements/PlacementsPage'
import { ReviewPage } from '@/pages/review/ReviewPage'
import { SetPasswordPage } from '@/pages/set-password/SetPasswordPage'
import { SettingsPage } from '@/pages/settings/SettingsPage'
import { UsersPage } from '@/pages/users/UsersPage'
import { AppShell } from './shell/AppShell'
import { RequireAuth } from './auth-gate'

const router = createBrowserRouter([
  { path: '/login', element: <LoginPage /> },
  { path: '/set-password', element: <SetPasswordPage /> },
  {
    element: (
      <RequireAuth>
        <AppShell />
      </RequireAuth>
    ),
    errorElement: <NotFoundPage />,
    children: [
      { path: '/', element: <HomePage /> },
      { path: '/placements', element: <PlacementsPage /> },
      { path: '/creatives', element: <CreativesPage /> },
      { path: '/review', element: <ReviewPage /> },
      { path: '/metrics', element: <MetricsPage /> },
      { path: '/marketplace', element: <MarketplacePage /> },
      { path: '/users', element: <UsersPage /> },
      { path: '/settings', element: <SettingsPage /> },
    ],
  },
  { path: '*', element: <NotFoundPage /> },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
