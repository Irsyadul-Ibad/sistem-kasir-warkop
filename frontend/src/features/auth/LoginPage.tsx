import { useEffect } from "react";

const loginUrl = import.meta.env.VITE_WEB_URL || `${window.location.protocol}//${window.location.hostname}:8000`;

export default function LoginPage() {
  useEffect(() => {
    window.location.replace(`${loginUrl}/login`);
  }, []);

  return (
    <main className="grid min-h-screen place-items-center p-6 text-center">
      <p className="font-semibold text-muted">Membuka login crew… <a href={`${loginUrl}/login`} className="font-bold text-brand underline">Lanjutkan</a></p>
    </main>
  );
}
