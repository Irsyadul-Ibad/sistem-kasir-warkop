import { api } from '../../lib/api'
import type { User } from '../../store/auth'

type LoginResponse = {
  token?: string
  user?: User
  data?: { token?: string; user?: User }
}

export async function login(username: string, password: string) {
  const res = await api<LoginResponse>('/login', {
    method: 'POST',
    body: { username, password },
  })

  const token = res.token ?? res.data?.token
  if (!token) throw new Error('Format response login tidak dikenali')

  let user = res.user ?? res.data?.user
  if (!user) {
    const me = await api<User | { data: User }>('/auth/me', { token })
    user = 'data' in me ? me.data : me
  }

  return { token, user }
}

export async function logout(token: string) {
  await api('/auth/logout', { method: 'POST', token })
}