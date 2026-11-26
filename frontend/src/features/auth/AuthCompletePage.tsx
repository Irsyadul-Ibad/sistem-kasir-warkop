import { useEffect, useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { api } from "../../lib/api";
import { useAuth, type User } from "../../store/auth";

type ExchangeResponse = { token: string; user: User };

export default function AuthCompletePage() {
  const [params] = useSearchParams();
  const navigate = useNavigate();
  const setAuth = useAuth((state) => state.setAuth);
  const code = params.get("code");
  const [error, setError] = useState(!code);

  useEffect(() => {
    if (!code) {
      return;
    }
    void api.post<ExchangeResponse>("/login/exchange", { code }, { timeout: 10000 })
      .then(({ data }) => {
        setAuth(data.token, data.user);
        navigate("/", { replace: true });
      })
      .catch(() => setError(true));
  }, [code, navigate, setAuth]);

  return <main className="grid min-h-screen place-items-center p-6"><div className="text-center">{error ? <><h1 className="text-xl font-extrabold">Sesi login tidak ditemukan</h1><a href={`${window.location.origin}/login`} className="mt-3 inline-block font-bold text-brand underline">Kembali ke login</a></> : <p className="font-semibold text-muted">Menyiapkan aplikasi...</p>}</div></main>;
}
