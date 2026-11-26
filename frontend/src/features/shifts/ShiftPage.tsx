import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import axios from "axios";
import { Link } from "react-router-dom";
import { api } from "../../lib/api";
import { formatRupiah } from "../../lib/format";

type Shift = {
  id: number;
  status: "active" | "closed";
  opening_cash: number;
  cash_sales: number;
  cash_expenses: number;
  expected_cash: number | null;
  actual_cash: number | null;
  difference: number | null;
  opened_at: string;
  closed_at: string | null;
};
type ApiData<T> = { data: T };

function errorMessage(error: unknown) {
  return axios.isAxiosError(error) ? error.response?.data?.message ?? "Data shift belum bisa disimpan." : "Data shift belum bisa disimpan.";
}

export default function ShiftPage() {
  const queryClient = useQueryClient();
  const [openingCash, setOpeningCash] = useState("");
  const [actualCash, setActualCash] = useState("");
  const [closedShift, setClosedShift] = useState<Shift | null>(null);
  const shiftQuery = useQuery({
    queryKey: ["shift", "current"],
    queryFn: async () => (await api.get<ApiData<Shift | null>>("/shifts/current")).data.data,
  });
  const openMutation = useMutation({
    mutationFn: async (amount: number) => (await api.post<ApiData<Shift>>("/shifts", { opening_cash: amount })).data.data,
    onSuccess: () => {
      setOpeningCash("");
      setClosedShift(null);
      void queryClient.invalidateQueries({ queryKey: ["shift", "current"] });
    },
  });
  const closeMutation = useMutation({
    mutationFn: async ({ shiftId, amount }: { shiftId: number; amount: number }) =>
      (await api.post<ApiData<Shift>>(`/shifts/${shiftId}/close`, { actual_cash: amount })).data.data,
    onSuccess: (shift) => {
      setClosedShift(shift);
      setActualCash("");
      void queryClient.invalidateQueries({ queryKey: ["shift", "current"] });
    },
  });
  const shift = shiftQuery.data;
  const error = openMutation.error ?? closeMutation.error;

  return (
    <main className="mx-auto min-h-screen max-w-3xl px-5 py-6 lg:px-10 lg:py-10">
      <header className="mb-6 flex items-center justify-between gap-4">
        <div><p className="font-semibold text-muted">Kasir · Shift</p><h1 className="mt-1 text-3xl font-extrabold">Buka atau tutup shift</h1></div>
        <Link to="/kasir" className="grid h-11 place-items-center rounded-xl border border-line bg-white px-4 font-bold">Kembali ke kasir</Link>
      </header>

      {error && <p role="alert" className="mb-4 rounded-2xl bg-red-50 p-4 font-semibold text-red-800">{errorMessage(error)}</p>}
      {shiftQuery.isError && <p role="alert" className="mb-4 rounded-2xl bg-red-50 p-4 font-semibold text-red-800">Status shift belum bisa dimuat. Periksa koneksi lalu coba lagi.</p>}
      {shiftQuery.isLoading ? <p className="py-8 text-center text-muted">Memuat shift...</p> : shift ? (
        <section className="rounded-3xl border border-line bg-white p-5 lg:p-7">
          <p className="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-sm font-bold text-emerald-800">Shift aktif</p>
          <p className="mt-3 text-sm text-muted">Dibuka {new Date(shift.opened_at).toLocaleString("id-ID", { timeZone: "Asia/Jakarta" })}</p>
          <div className="mt-5 grid gap-3 sm:grid-cols-2">
            <article className="rounded-2xl bg-slate-50 p-4"><p className="text-sm font-semibold text-muted">Kas awal</p><p className="mt-1 text-xl font-extrabold">{formatRupiah(shift.opening_cash)}</p></article>
            <article className="rounded-2xl bg-slate-50 p-4"><p className="text-sm font-semibold text-muted">Penjualan tunai</p><p className="mt-1 text-xl font-extrabold">{formatRupiah(shift.cash_sales)}</p></article>
          </div>
          <form onSubmit={(event) => { event.preventDefault(); closeMutation.mutate({ shiftId: shift.id, amount: Number(actualCash) }); }} className="mt-6 border-t border-line pt-5">
            <label className="block text-sm font-bold text-muted">Kas aktual saat tutup
              <div className="mt-2 flex h-14 items-center rounded-2xl border border-line bg-white px-4"><span className="mr-2 font-bold">Rp</span><input required type="number" min="0" step="1" value={actualCash} onChange={(event) => setActualCash(event.target.value)} className="min-w-0 flex-1 bg-transparent text-xl font-extrabold text-ink outline-none" placeholder="0" /></div>
            </label>
            <button disabled={closeMutation.isPending || actualCash === ""} className="mt-4 min-h-12 rounded-xl bg-brand px-7 font-extrabold text-white disabled:opacity-50">{closeMutation.isPending ? "Menghitung..." : "Tutup shift"}</button>
          </form>
        </section>
      ) : closedShift ? (
        <section className="rounded-3xl border border-line bg-white p-5 lg:p-7">
          <p className="font-bold text-emerald-800">Shift berhasil ditutup</p>
          <h2 className="mt-2 text-xl font-extrabold">Ringkasan kas</h2>
          <dl className="mt-4 divide-y divide-line">
            <div className="flex justify-between gap-4 py-3"><dt className="text-muted">Kas awal</dt><dd className="font-bold">{formatRupiah(closedShift.opening_cash)}</dd></div>
            <div className="flex justify-between gap-4 py-3"><dt className="text-muted">Penjualan tunai</dt><dd className="font-bold">{formatRupiah(closedShift.cash_sales)}</dd></div>
            <div className="flex justify-between gap-4 py-3"><dt className="text-muted">Pengeluaran tunai</dt><dd className="font-bold">{formatRupiah(closedShift.cash_expenses)}</dd></div>
            <div className="flex justify-between gap-4 py-3"><dt className="text-muted">Kas seharusnya</dt><dd className="font-bold">{formatRupiah(closedShift.expected_cash ?? 0)}</dd></div>
            <div className="flex justify-between gap-4 py-3"><dt className="text-muted">Kas aktual</dt><dd className="font-bold">{formatRupiah(closedShift.actual_cash ?? 0)}</dd></div>
            <div className="flex justify-between gap-4 py-3"><dt className="font-extrabold">Selisih</dt><dd className={`font-extrabold ${(closedShift.difference ?? 0) < 0 ? "text-red-700" : "text-emerald-800"}`}>{formatRupiah(closedShift.difference ?? 0)}</dd></div>
          </dl>
          <button onClick={() => setClosedShift(null)} className="mt-3 min-h-12 rounded-xl bg-brand px-6 font-bold text-white">Buka shift berikutnya</button>
        </section>
      ) : (
        <section className="rounded-3xl border border-line bg-white p-5 lg:p-7">
          <p className="inline-flex rounded-full bg-amber-50 px-3 py-1 text-sm font-bold text-amber-900">Belum ada shift aktif</p>
          <h2 className="mt-3 text-xl font-extrabold">Mulai shift kasir</h2>
          <p className="mt-1 text-sm text-muted">Catat uang tunai awal di laci sebelum mulai melayani.</p>
          <form onSubmit={(event) => { event.preventDefault(); openMutation.mutate(Number(openingCash)); }} className="mt-5">
            <label className="block text-sm font-bold text-muted">Kas awal
              <div className="mt-2 flex h-14 items-center rounded-2xl border border-line bg-white px-4"><span className="mr-2 font-bold">Rp</span><input required type="number" min="0" step="1" value={openingCash} onChange={(event) => setOpeningCash(event.target.value)} className="min-w-0 flex-1 bg-transparent text-xl font-extrabold text-ink outline-none" placeholder="0" /></div>
            </label>
            <button disabled={openMutation.isPending || openingCash === ""} className="mt-4 min-h-12 rounded-xl bg-brand px-7 font-extrabold text-white disabled:opacity-50">{openMutation.isPending ? "Membuka..." : "Buka shift"}</button>
          </form>
        </section>
      )}
    </main>
  );
}
