import { useNavigate } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import { api } from "../../lib/api";
import { formatRupiah } from "../../lib/format";
import { useAuth } from "../../store/auth";
import type { Menu } from "../../store/cart";

type Transaction = {
  invoice_no: string;
  total: number;
  payment_method: string;
  paid_at: string;
};
type TransactionPage = { data: Transaction[]; meta: { total: number } };
type MenuCollection = { data: Menu[] };

export default function HomePage() {
  const navigate = useNavigate();
  const user = useAuth((state) => state.user);
  const logout = useAuth((state) => state.logout);

  const transactionsQuery = useQuery({
    queryKey: ["owner", "transactions-today"],
    queryFn: async () => (await api.get<TransactionPage>("/transactions", { params: { per_page: 1 } })).data,
  });
  const menusQuery = useQuery({
    queryKey: ["menus"],
    queryFn: async () => (await api.get<MenuCollection>("/menus")).data.data,
  });

  async function signOut() {
    try {
      await api.post("/auth/logout");
    } catch {
      // Owner tetap bisa keluar saat server sedang tidak terjangkau.
    }
    logout();
  }

  const latestTransaction = transactionsQuery.data?.data[0];

  return (
    <main className="mx-auto min-h-screen max-w-6xl px-5 py-6 lg:px-10 lg:py-10">
      <header className="flex items-center justify-between gap-4">
        <div>
          <p className="font-semibold text-muted">Panel owner</p>
          <h1 className="mt-1 text-2xl font-extrabold lg:text-3xl">Halo, {user?.name}</h1>
        </div>
        <button onClick={() => void signOut()} className="h-11 rounded-xl border border-line bg-white px-4 text-sm font-bold hover:border-brand">
          Keluar
        </button>
      </header>

      <section aria-label="Ringkasan hari ini" className="mt-8 grid gap-4 sm:grid-cols-2">
        <article className="rounded-3xl bg-brand p-6 text-white">
          <p className="font-semibold text-white/80">Transaksi hari ini</p>
          <p className="mt-2 text-4xl font-extrabold">
            {transactionsQuery.isLoading ? "—" : transactionsQuery.data?.meta.total ?? "—"}
          </p>
          <p className="mt-1 text-sm text-white/80">Semua kasir</p>
        </article>
        <article className="rounded-3xl border border-line bg-white p-6">
          <p className="font-semibold text-muted">Menu aktif</p>
          <p className="mt-2 text-4xl font-extrabold">
            {menusQuery.isLoading ? "—" : menusQuery.data?.length ?? "—"}
          </p>
          <p className="mt-1 text-sm text-muted">Siap dijual</p>
        </article>
      </section>

      {(transactionsQuery.isError || menusQuery.isError) && (
        <div role="alert" className="mt-4 rounded-2xl bg-red-50 p-4 text-sm font-semibold text-red-800">
          Ringkasan belum bisa dimuat. Periksa koneksi lalu coba lagi.
          <button
            onClick={() => {
              void transactionsQuery.refetch();
              void menusQuery.refetch();
            }}
            className="ml-2 underline"
          >
            Coba lagi
          </button>
        </div>
      )}

      <section className="mt-8">
        <h2 className="text-lg font-extrabold">Akses cepat</h2>
        <div className="mt-3 grid gap-3 sm:grid-cols-2">
          <button onClick={() => navigate("/kasir")} className="rounded-2xl border border-line bg-white p-5 text-left hover:border-brand">
            <span className="block font-extrabold">Buka kasir</span>
            <span className="mt-1 block text-sm text-muted">Buat transaksi penjualan</span>
          </button>
          <button onClick={() => navigate("/owner/menus")} className="rounded-2xl border border-line bg-white p-5 text-left hover:border-brand">
            <span className="block font-extrabold">Menu & harga</span>
            <span className="mt-1 block text-sm text-muted">Tambah menu, ubah harga, atau nonaktifkan</span>
          </button>
          <button onClick={() => navigate("/expenses")} className="rounded-2xl border border-line bg-white p-5 text-left hover:border-brand">
            <span className="block font-extrabold">Pengeluaran</span>
            <span className="mt-1 block text-sm text-muted">Catat dan pantau biaya operasional</span>
          </button>
          <button onClick={() => navigate("/debts")} className="rounded-2xl border border-line bg-white p-5 text-left hover:border-brand">
            <span className="block font-extrabold">Bon pelanggan</span>
            <span className="mt-1 block text-sm text-muted">Lihat sisa bon dan catat pelunasan</span>
          </button>
          <a href="/owner" className="rounded-2xl border border-line bg-white p-5 text-left hover:border-brand">
            <span className="block font-extrabold">Dashboard web owner</span>
            <span className="mt-1 block text-sm text-muted">Buka dashboard owner di Laravel</span>
          </a>
        </div>
      </section>

      <section className="mt-8 rounded-3xl border border-line bg-white p-5 lg:p-6">
        <div className="flex items-center justify-between gap-4">
          <h2 className="text-lg font-extrabold">Transaksi terbaru hari ini</h2>
          <button onClick={() => navigate("/kasir")} className="text-sm font-bold text-brand">Buka kasir</button>
        </div>
        {transactionsQuery.isLoading ? (
          <p className="mt-5 text-sm text-muted">Memuat transaksi...</p>
        ) : latestTransaction ? (
          <div className="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4">
            <div>
              <p className="font-bold">{latestTransaction.invoice_no}</p>
              <p className="mt-1 text-sm text-muted">Pembayaran {latestTransaction.payment_method}</p>
            </div>
            <p className="text-lg font-extrabold">{formatRupiah(latestTransaction.total)}</p>
          </div>
        ) : (
          <p className="mt-5 border-t border-line pt-4 text-sm text-muted">Belum ada transaksi hari ini.</p>
        )}
      </section>
    </main>
  );
}
