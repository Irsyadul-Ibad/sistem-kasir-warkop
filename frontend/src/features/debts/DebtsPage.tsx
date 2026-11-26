import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import axios from "axios";
import { Link, useLocation } from "react-router-dom";
import { api } from "../../lib/api";
import { formatRupiah } from "../../lib/format";

type Debt = {
  id: number;
  invoice_no: string;
  customer: { id: number; name: string; phone: string | null } | null;
  total: number;
  paid_amount: number;
  remaining_amount: number;
  created_at: string;
  age_days: number;
};
type DebtList = { data: Debt[] };
type PaymentMethod = "cash" | "qris" | "transfer";
const emptyDebts: Debt[] = [];

const paymentMethods: { id: PaymentMethod; label: string }[] = [
  { id: "cash", label: "Tunai" },
  { id: "qris", label: "QRIS" },
  { id: "transfer", label: "Transfer" },
];

function errorMessage(error: unknown) {
  return axios.isAxiosError(error) ? error.response?.data?.message ?? "Pelunasan belum bisa disimpan." : "Pelunasan belum bisa disimpan.";
}

export default function DebtsPage() {
  const queryClient = useQueryClient();
  const location = useLocation();
  const [search, setSearch] = useState("");
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [amount, setAmount] = useState("");
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod>("cash");
  const debtsQuery = useQuery({
    queryKey: ["debts"],
    queryFn: async () => (await api.get<DebtList>("/debts")).data.data,
  });
  const payMutation = useMutation({
    mutationFn: async ({ id, amount: paid, method }: { id: number; amount: number; method: PaymentMethod }) =>
      api.post(`/debts/${id}/payments`, { amount: paid, payment_method: method }),
    onSuccess: () => {
      setSelectedId(null);
      setAmount("");
      void queryClient.invalidateQueries({ queryKey: ["debts"] });
      void queryClient.invalidateQueries({ queryKey: ["owner"] });
    },
  });
  const debts = debtsQuery.data ?? emptyDebts;
  const visibleDebts = useMemo(() => {
    const query = search.trim().toLowerCase();
    return debts.filter((debt) => `${debt.customer?.name ?? ""} ${debt.customer?.phone ?? ""} ${debt.invoice_no}`.toLowerCase().includes(query));
  }, [debts, search]);
  const outstanding = debts.reduce((sum, debt) => sum + debt.remaining_amount, 0);
  const selectedDebt = debts.find((debt) => debt.id === selectedId);

  return (
    <div className="min-h-screen pb-20 lg:pb-8">
      <main className="mx-auto max-w-5xl px-5 py-6 lg:px-10 lg:py-10">
        <header className="mb-6 flex flex-wrap items-end justify-between gap-4">
          <div>
            <p className="font-semibold text-muted">Total bon berjalan · {formatRupiah(outstanding)}</p>
            <h1 className="mt-1 text-3xl font-extrabold">Bon pelanggan</h1>
          </div>
          <label className="flex h-12 min-w-64 items-center rounded-xl border border-line bg-white px-4">
            <span className="sr-only">Cari pelanggan atau invoice</span>
            <input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Cari nama, telepon, invoice" className="w-full bg-transparent outline-none" />
          </label>
        </header>

        {debtsQuery.isError && <p role="alert" className="rounded-2xl bg-red-50 p-4 font-semibold text-red-800">Daftar bon belum bisa dimuat. Periksa koneksi lalu coba lagi.</p>}
        {payMutation.error && <p role="alert" className="mb-4 rounded-2xl bg-red-50 p-4 font-semibold text-red-800">{errorMessage(payMutation.error)}</p>}
        {debtsQuery.isLoading ? <p className="py-8 text-center text-muted">Memuat daftar bon...</p> : visibleDebts.length ? (
          <ul className="space-y-3">
            {visibleDebts.map((debt) => (
              <li key={debt.id} className="rounded-3xl border border-line bg-white p-5 lg:p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                  <div>
                    <h2 className="text-lg font-extrabold">{debt.customer?.name ?? "Pelanggan"}</h2>
                    <p className="mt-1 text-sm text-muted">{debt.invoice_no} · {debt.customer?.phone ?? "Tanpa nomor telepon"}</p>
                    <p className="mt-1 text-sm text-muted">Bon {new Date(debt.created_at).toLocaleDateString("id-ID", { timeZone: "Asia/Jakarta" })} · umur {debt.age_days} hari</p>
                  </div>
                  <div className="text-right">
                    <p className="text-sm font-semibold text-muted">Sisa bon</p>
                    <p className="text-2xl font-extrabold text-red-700">{formatRupiah(debt.remaining_amount)}</p>
                  </div>
                </div>
                <div className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4">
                  <p className="text-sm text-muted">Total {formatRupiah(debt.total)} · terbayar {formatRupiah(debt.paid_amount)}</p>
                  {debt.customer?.phone && <a href={`tel:${debt.customer.phone}`} className="text-sm font-bold text-brand">Hubungi pelanggan</a>}
                  <button onClick={() => { setSelectedId(selectedId === debt.id ? null : debt.id); setAmount(String(debt.remaining_amount)); }} className="min-h-11 rounded-xl bg-brand px-5 font-bold text-white">
                    {selectedId === debt.id ? "Tutup pelunasan" : "Catat pelunasan"}
                  </button>
                </div>
                {selectedDebt?.id === debt.id && (
                  <div className="mt-4 rounded-2xl bg-slate-50 p-4">
                    <label className="block text-sm font-bold text-muted">Nominal dibayar
                      <div className="mt-1 flex h-12 items-center rounded-xl border border-line bg-white px-3"><span className="mr-2 font-bold">Rp</span><input type="number" min="1" max={debt.remaining_amount} step="1" value={amount} onChange={(event) => setAmount(event.target.value)} className="min-w-0 flex-1 bg-transparent font-extrabold text-ink outline-none" /></div>
                    </label>
                    <div className="mt-3 flex flex-wrap gap-2">
                      {paymentMethods.map((method) => <button key={method.id} type="button" onClick={() => setPaymentMethod(method.id)} aria-pressed={paymentMethod === method.id} className={`min-h-10 rounded-full border px-4 font-bold ${paymentMethod === method.id ? "border-brand bg-brand text-white" : "border-line bg-white"}`}>{method.label}</button>)}
                    </div>
                    <div className="mt-3 flex justify-end gap-2">
                      <button onClick={() => setSelectedId(null)} className="min-h-11 rounded-xl border border-line bg-white px-5 font-bold">Batal</button>
                      <button onClick={() => payMutation.mutate({ id: debt.id, amount: Number(amount), method: paymentMethod })} disabled={payMutation.isPending || !Number(amount) || Number(amount) > debt.remaining_amount} className="min-h-11 rounded-xl bg-brand px-5 font-bold text-white disabled:opacity-50">{payMutation.isPending ? "Menyimpan..." : "Simpan pembayaran"}</button>
                    </div>
                  </div>
                )}
              </li>
            ))}
          </ul>
        ) : (
          <div className="rounded-3xl border border-line bg-white p-10 text-center">
            <h2 className="font-extrabold">{debts.length ? "Bon tidak ditemukan" : "Tidak ada bon berjalan"}</h2>
            <p className="mt-2 text-sm text-muted">Bon baru yang dibuat dari kasir akan muncul di sini.</p>
          </div>
        )}
      </main>
      <nav className="fixed inset-x-0 bottom-0 z-10 grid h-16 grid-cols-4 border-t border-line bg-white lg:hidden" aria-label="Navigasi kasir">
        <Link to="/kasir" className="grid place-items-center font-bold text-muted">Kasir</Link>
        <Link to="/expenses" className="grid place-items-center font-bold text-muted">Pengeluaran</Link>
        <Link to="/debts" className={`grid place-items-center font-bold ${location.pathname === "/debts" ? "text-brand" : "text-muted"}`}>Bon</Link>
        <Link to="/transactions" className="grid place-items-center font-bold text-muted">Riwayat</Link>
      </nav>
    </div>
  );
}
