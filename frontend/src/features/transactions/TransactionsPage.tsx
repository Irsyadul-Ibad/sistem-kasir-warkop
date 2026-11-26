import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useNavigate, useParams } from "react-router-dom";
import { api } from "../../lib/api";
import { formatRupiah } from "../../lib/format";
import { useAuth } from "../../store/auth";

type Transaction = {
  id: number;
  invoice_no: string;
  total: number;
  payment_method: string;
  status: string;
  paid_at: string | null;
  table_label?: string | null;
  void_reason?: string | null;
  items?: { id: number; menu_name_snapshot: string; price_snapshot: number; qty: number; line_total: number; note?: string | null }[];
};
type TransactionPage = {
  data: Transaction[];
  meta: { total: number; current_page: number; last_page: number };
};

const methodLabels: Record<string, string> = { cash: "Tunai", qris: "QRIS", transfer: "Transfer", debt: "Bon" };

function todayInJakarta() {
  const parts = new Intl.DateTimeFormat("en-CA", {
    timeZone: "Asia/Jakarta",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).formatToParts(new Date());
  const value = Object.fromEntries(parts.map(({ type, value }) => [type, value]));
  return `${value.year}-${value.month}-${value.day}`;
}

export function TransactionsPage() {
  const navigate = useNavigate();
  const [date, setDate] = useState(todayInJakarta);
  const [page, setPage] = useState(1);
  const query = useQuery({
    queryKey: ["transactions", date, page],
    queryFn: async () => (await api.get<TransactionPage>("/transactions", { params: { date, page, per_page: 20 } })).data,
  });

  return (
    <main className="mx-auto min-h-screen max-w-6xl px-5 py-6 lg:px-10 lg:py-10">
      <header className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <button onClick={() => navigate(-1)} className="h-10 rounded-lg pr-3 text-sm font-bold text-brand">← Kembali</button>
          <h1 className="mt-2 text-3xl font-extrabold">Riwayat transaksi</h1>
        </div>
        <label className="text-sm font-bold text-muted">
          Tanggal
          <input
            type="date"
            value={date}
            onChange={(event) => { setDate(event.target.value); setPage(1); }}
            className="mt-1 block h-11 rounded-xl border border-line bg-white px-3 text-ink"
          />
        </label>
      </header>

      <section className="mt-6 overflow-hidden rounded-3xl border border-line bg-white">
        <div className="border-b border-line px-5 py-4 font-bold">
          {query.data ? `${query.data.meta.total} transaksi` : "Transaksi"} · {date}
        </div>
        {query.isLoading ? (
          <p className="p-6 text-muted">Memuat transaksi...</p>
        ) : query.isError ? (
          <div role="alert" className="p-6">
            <p className="font-bold">Riwayat belum bisa dimuat.</p>
            <button onClick={() => void query.refetch()} className="mt-3 h-11 rounded-xl bg-brand px-4 font-bold text-white">Coba lagi</button>
          </div>
        ) : !query.data ? (
          <p className="p-6 text-muted">Memuat transaksi...</p>
        ) : query.data.data.length === 0 ? (
          <p className="p-10 text-center text-muted">Belum ada transaksi pada tanggal ini.</p>
        ) : (
          <div className="divide-y divide-line">
            {query.data.data.map((transaction) => (
              <button
                key={transaction.id}
                onClick={() => navigate(`/transactions/${transaction.id}`)}
                className="flex min-h-20 w-full flex-wrap items-center justify-between gap-3 px-5 py-4 text-left hover:bg-slate-50"
              >
                <span>
                  <span className="block font-extrabold">{transaction.invoice_no}</span>
                  <span className="mt-1 block text-sm text-muted">
                    {transaction.paid_at ? new Date(transaction.paid_at).toLocaleTimeString("id-ID", { timeZone: "Asia/Jakarta", hour: "2-digit", minute: "2-digit" }) : "—"}
                    {transaction.table_label ? ` · ${transaction.table_label}` : ""}
                  </span>
                </span>
                <span className="text-sm font-semibold text-muted">{transaction.status === "void" ? "Di-void" : methodLabels[transaction.payment_method] ?? transaction.payment_method}</span>
                <span className="text-lg font-extrabold">{formatRupiah(transaction.total)}</span>
              </button>
            ))}
          </div>
        )}
        {query.data && query.data.meta.last_page > 1 && (
          <div className="flex items-center justify-between border-t border-line px-5 py-3">
            <button onClick={() => setPage((value) => Math.max(1, value - 1))} disabled={page <= 1} className="h-11 rounded-xl border border-line px-4 font-bold disabled:opacity-40">Sebelumnya</button>
            <span className="text-sm font-semibold text-muted">Halaman {page} dari {query.data.meta.last_page}</span>
            <button onClick={() => setPage((value) => Math.min(query.data!.meta.last_page, value + 1))} disabled={page >= query.data.meta.last_page} className="h-11 rounded-xl border border-line px-4 font-bold disabled:opacity-40">Berikutnya</button>
          </div>
        )}
      </section>
    </main>
  );
}

export function TransactionDetailPage() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { id } = useParams();
  const isOwner = useAuth((state) => state.user?.role === "owner");
  const [voidReason, setVoidReason] = useState("");
  const [showVoidForm, setShowVoidForm] = useState(false);
  const query = useQuery({
    queryKey: ["transaction", id],
    queryFn: async () => (await api.get<{ data: Transaction }>(`/transactions/${id}`)).data.data,
    enabled: Boolean(id),
  });
  const transaction = query.data;
  const voidMutation = useMutation({
    mutationFn: async () => (await api.post(`/transactions/${id}/void`, { reason: voidReason.trim() })).data,
    onSuccess: () => {
      setShowVoidForm(false);
      setVoidReason("");
      void queryClient.invalidateQueries({ queryKey: ["transaction", id] });
      void queryClient.invalidateQueries({ queryKey: ["transactions"] });
      void queryClient.invalidateQueries({ queryKey: ["owner"] });
    },
  });

  return (
    <main className="mx-auto min-h-screen max-w-3xl px-5 py-6 lg:px-10 lg:py-10">
      <button onClick={() => navigate(-1)} className="h-11 rounded-lg pr-3 font-bold text-brand">← Riwayat transaksi</button>
      {query.isLoading ? <p className="mt-6 text-muted">Memuat transaksi...</p> : query.isError || !transaction ? (
        <p role="alert" className="mt-6 rounded-2xl bg-red-50 p-5 font-semibold text-red-800">Detail transaksi tidak dapat dimuat.</p>
      ) : (
        <section className="mt-5 rounded-3xl border border-line bg-white p-5 lg:p-7">
          <p className="text-sm font-semibold text-muted">Detail transaksi</p>
          <h1 className="mt-1 text-2xl font-extrabold">{transaction.invoice_no}</h1>
          <p className="mt-1 text-sm text-muted">{transaction.paid_at ? new Date(transaction.paid_at).toLocaleString("id-ID", { timeZone: "Asia/Jakarta" }) : "—"}</p>
          {transaction.table_label && <p className="mt-1 text-sm font-semibold text-muted">{transaction.table_label}</p>}
          <div className="mt-6 divide-y divide-line border-y border-line">
            {(transaction.items ?? []).map((item) => (
              <div key={item.id} className="py-4">
                <div className="flex justify-between gap-3">
                  <p className="font-bold">{item.qty} × {item.menu_name_snapshot}</p>
                  <p className="shrink-0 font-bold">{formatRupiah(item.line_total)}</p>
                </div>
                <p className="mt-1 text-sm text-muted">{formatRupiah(item.price_snapshot)} per item{item.note ? ` · ${item.note}` : ""}</p>
              </div>
            ))}
          </div>
          <div className="mt-5 flex justify-between gap-4 text-lg font-extrabold">
            <span>Total · {methodLabels[transaction.payment_method] ?? transaction.payment_method}</span>
            <span>{formatRupiah(transaction.total)}</span>
          </div>
          {transaction.status === "void" && <div className="mt-4 rounded-xl bg-red-50 p-4 text-sm font-semibold text-red-800">Transaksi di-void: {transaction.void_reason}</div>}
          {isOwner && transaction.status === "paid" && transaction.payment_method !== "debt" && !showVoidForm && (
            <button onClick={() => setShowVoidForm(true)} className="mt-6 h-11 rounded-xl border border-red-200 px-4 font-bold text-red-700">Void transaksi</button>
          )}
          {showVoidForm && (
            <div className="mt-5 rounded-2xl bg-red-50 p-4">
              <label className="block text-sm font-bold text-red-900">Alasan void
                <textarea required minLength={3} maxLength={500} value={voidReason} onChange={(event) => setVoidReason(event.target.value)} className="mt-2 min-h-24 w-full rounded-xl border border-red-200 bg-white p-3 text-ink" placeholder="Tuliskan alasan pembatalan" />
              </label>
              {voidMutation.error && <p role="alert" className="mt-2 text-sm font-semibold text-red-800">Tidak dapat melakukan void. Coba lagi.</p>}
              <div className="mt-3 flex justify-end gap-2">
                <button onClick={() => setShowVoidForm(false)} className="min-h-11 rounded-xl border border-line bg-white px-4 font-bold">Batal</button>
                <button onClick={() => voidMutation.mutate()} disabled={voidMutation.isPending || voidReason.trim().length < 3} className="min-h-11 rounded-xl bg-red-700 px-4 font-bold text-white disabled:opacity-50">{voidMutation.isPending ? "Menyimpan..." : "Konfirmasi void"}</button>
              </div>
            </div>
          )}
        </section>
      )}
    </main>
  );
}
