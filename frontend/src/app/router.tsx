import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import { LoginPage } from '@/pages/login/LoginPage'
import { NotFoundPage } from '@/pages/not-found/NotFoundPage'
import { PlacementsPage } from '@/pages/placements/PlacementsPage'
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
    children: [{ path: '/', element: <PlacementsPage /> }],
  },
  { path: '*', element: <NotFoundPage /> },
])

export function AppRouter() {
  return <RouterProvider router={router} />
}
