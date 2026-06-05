import { useNavigate } from 'react-router-dom'
import { HomeView, useHomePage } from '@/features/home'

export function HomePage() {
  const page = useHomePage()
  const navigate = useNavigate()

  return (
    <HomeView
      done={page.done}
      onNavigate={(to) => {
        void navigate(to)
      }}
    />
  )
}
