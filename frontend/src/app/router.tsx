import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import { CreativesPage } from '@/pages/creatives/CreativesPage'
import { LoginPage } from '@/pages/login/LoginPage'
import { MetricsPage } from '@/pages/metrics/MetricsPage'
import { NotFoundPage } from '@/pages/not-found/NotFoundPage'
import { PlacementsPage } from '@/pages/placements/PlacementsPage'
import { ReviewPage } from '@/pages/review/ReviewPage'
import { AppShell } from './shell/AppShell'
import { RequireAuth } from './auth-gate'

const router = createBrowserRouter([
  { path: '/login', element: <LoginPage /> },
  {
    element: (
      <RequireAuth>
        <AppShell />
      </RequireAuth>
    ),
    errorElement: <NotFoundPage />,
    children: [
      { path: '/', element: <PlacementsPage /> },
      { path: '/creatives', element: <CreativesPage /> },
      { path: '/review', element: <ReviewPage /> },
      { path: '/metrics', element: <MetricsPage /> },
    ],
  },
  { path: '*', element: <NotFoundPage /> },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
