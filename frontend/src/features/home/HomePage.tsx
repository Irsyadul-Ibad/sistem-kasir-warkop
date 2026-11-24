import { useNavigate } from 'react-router-dom'
import { useAuth } from '../../store/auth'
import { logout } from '../auth/authApi'

export default function HomePage() {
  const navigate = useNavigate()
  const { token, user, clear } = useAuth()

  async function onLogout() {
    try {
      if (token) await logout(token)
    } finally {
      clear()
      navigate('/login', { replace: true })
    }
  }

  return (
    <main className="home">
      <h1>Halo, {user?.name}</h1>
      <p>Peran: {user?.role === 'owner' ? 'Owner' : 'Kasir'}</p>
      <button onClick={onLogout}>Keluar</button>
    </main>
  )
}