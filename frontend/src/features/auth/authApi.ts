const BASE_URL = import.meta.env.VITE_API_URL

type Options = {
  method?: string
  body?: unknown
  token?: string | null
}

export async function api<T = unknown>(path: string, opts: Options = {}): Promise<T> {
  const res = await fetch(`${BASE_URL}${path}`, {
    method: opts.method ?? 'GET',
    headers: {
      Accept: 'application/json',
      ...(opts.body ? { 'Content-Type': 'application/json' } : {}),
      ...(opts.token ? { Authorization: `Bearer ${opts.token}` } : {}),
    },
    body: opts.body ? JSON.stringify(opts.body) : undefined,
  })

  if (res.status === 204) return undefined as T

  const data = await res.json().catch(() => ({}))
  if (!res.ok) throw new Error(data.message ?? 'Terjadi kesalahan')
  return data as T
}