import { useState, type FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import axios from "axios";
import { Link, useLocation } from "react-router-dom";
import { api } from "../../lib/api";
import { formatRupiah } from "../../lib/format";
import { useAuth } from "../../store/auth";

type Category = { id: number; name: string };
type Expense = {
  id: number;
  amount: number;
  expense_date: string;
  description: string | null;
  receipt_url: string | null;
  category: Category;
  user?: { id: number; name: string };
  created_at: string;
};
type ExpenseList = {
  data: Expense[];
  summary: { date: string; total: number };
};
type ExpenseValues = { category_id: number; amount: number; expense_date: string; description: string };

function localToday() {
  const date = new Date();
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
}

function apiError(error: unknown) {
  return axios.isAxiosError(error)
    ? error.response?.data?.message ?? "Pengeluaran belum bisa disimpan."
    : "Pengeluaran belum bisa disimpan.";
}

export default function ExpensesPage() {
  const queryClient = useQueryClient();
  const location = useLocation();
  const user = useAuth((state) => state.user);
  const isOwner = user?.role === "owner";
  const [date, setDate] = useState(localToday);
  const [amount, setAmount] = useState("");
  const [categoryId, setCategoryId] = useState<number | null>(null);
  const [description, setDescription] = useState("");
  const [receipt, setReceipt] = useState<File | null>(null);
  const [editing, setEditing] = useState<Expense | null>(null);
  const [newCategoryName, setNewCategoryName] = useState("");
  const [showNewCategory, setShowNewCategory] = useState(false);
  const [receiptError, setReceiptError] = useState("");

  const categoriesQuery = useQuery({
    queryKey: ["categories", "expense"],
    queryFn: async () => (await api.get<{ data: Category[] }>("/categories", { params: { type: "expense" } })).data.data,
  });
  const expensesQuery = useQuery({
    queryKey: ["expenses", date],
    queryFn: async () => (await api.get<ExpenseList>("/expenses", { params: { date, per_page: 100 } })).data,
  });
  const refresh = () => queryClient.invalidateQueries({ queryKey: ["expenses"] });
  const createMutation = useMutation({
    mutationFn: async (values: ExpenseValues) => {
      const data = new FormData();
      data.set("category_id", String(values.category_id));
      data.set("amount", String(values.amount));
      data.set("expense_date", values.expense_date);
      if (values.description) data.set("description", values.description);
      if (receipt) data.set("receipt", receipt);
      return api.post("/expenses", data);
    },
    onSuccess: () => {
      setAmount("");
      setDescription("");
      setReceipt(null);
      refresh();
    },
  });
  const updateMutation = useMutation({
    mutationFn: async (values: ExpenseValues) => api.put(`/expenses/${editing?.id}`, values),
    onSuccess: () => {
      setEditing(null);
      setAmount("");
      setDescription("");
      refresh();
    },
  });
  const deleteMutation = useMutation({
    mutationFn: async (id: number) => api.delete(`/expenses/${id}`),
    onSuccess: refresh,
  });
  const categoryMutation = useMutation({
    mutationFn: async (name: string) => api.post("/categories", { name, type: "expense" }),
    onSuccess: () => {
      setNewCategoryName("");
      setShowNewCategory(false);
      void queryClient.invalidateQueries({ queryKey: ["categories", "expense"] });
    },
  });

  function startEdit(expense: Expense) {
    setEditing(expense);
    setCategoryId(expense.category.id);
    setAmount(String(expense.amount));
    setDate(expense.expense_date);
    setDescription(expense.description ?? "");
  }

  function cancelEdit() {
    setEditing(null);
    setAmount("");
    setDescription("");
    setReceipt(null);
    setDate(localToday());
  }

  async function openReceipt(url: string) {
    const preview = window.open("about:blank", "_blank");
    if (!preview) return;
    try {
      const response = await api.get<Blob>(url, { responseType: "blob" });
      const blobUrl = URL.createObjectURL(response.data);
      preview.location.href = blobUrl;
      window.setTimeout(() => URL.revokeObjectURL(blobUrl), 60_000);
      setReceiptError("");
    } catch {
      preview.close();
      setReceiptError("Foto nota tidak dapat dibuka.");
    }
  }

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!categoryId) return;
    const values = { category_id: categoryId, amount: Number(amount), expense_date: date, description: description.trim() };
    if (editing) updateMutation.mutate(values);
    else createMutation.mutate(values);
  }

  const busy = createMutation.isPending || updateMutation.isPending;
  const saveError = createMutation.error ?? updateMutation.error ?? categoryMutation.error ?? deleteMutation.error;
  const expenses = expensesQuery.data?.data ?? [];

  return (
    <div className="min-h-screen pb-20 lg:pb-8">
      <main className="mx-auto max-w-5xl px-5 py-6 lg:px-10 lg:py-10">
        <header className="mb-6 flex flex-wrap items-end justify-between gap-4">
          <div>
            <p className="font-semibold text-muted">{date === localToday() ? "Hari ini" : date} · total {formatRupiah(expensesQuery.data?.summary.total ?? 0)}</p>
            <h1 className="mt-1 text-3xl font-extrabold">Pengeluaran</h1>
          </div>
          <label className="text-sm font-bold text-muted">
            Tanggal
            <input type="date" value={date} onChange={(event) => { setDate(event.target.value); setEditing(null); }} className="mt-1 block h-11 rounded-xl border border-line bg-white px-3 text-ink" />
          </label>
        </header>

        <form onSubmit={submit} className="rounded-3xl border border-line bg-white p-5 lg:p-7">
          <div className="grid gap-5 md:grid-cols-2">
            <label className="block text-sm font-bold text-muted">
              Nominal
              <div className="mt-2 flex h-14 items-center rounded-2xl border border-line px-4">
                <span className="mr-2 font-extrabold text-muted">Rp</span>
                <input required type="number" min="1" step="1" inputMode="numeric" value={amount} onChange={(event) => setAmount(event.target.value)} placeholder="0" className="h-full min-w-0 flex-1 bg-transparent text-2xl font-extrabold text-ink outline-none" />
              </div>
            </label>
            <label className="block text-sm font-bold text-muted">
              Tanggal pengeluaran
              <input required type="date" value={date} onChange={(event) => setDate(event.target.value)} className="mt-2 h-14 w-full rounded-2xl border border-line bg-white px-4 text-base text-ink" />
            </label>
          </div>

          <div className="mt-5">
            <div className="mb-2 flex items-center justify-between gap-3">
              <p className="text-sm font-bold text-muted">Kategori</p>
              {isOwner && <button type="button" onClick={() => setShowNewCategory((show) => !show)} className="text-sm font-bold text-brand">{showNewCategory ? "Tutup" : "+ Kategori"}</button>}
            </div>
            {categoriesQuery.isLoading ? <p className="text-sm text-muted">Memuat kategori...</p> : (
              <div className="flex flex-wrap gap-2">
                {(categoriesQuery.data ?? []).map((category) => (
                  <button key={category.id} type="button" onClick={() => setCategoryId(category.id)} aria-pressed={categoryId === category.id} className={`min-h-11 rounded-full border px-5 font-bold ${categoryId === category.id ? "border-brand bg-brand text-white" : "border-line bg-white hover:border-brand"}`}>
                    {category.name}
                  </button>
                ))}
              </div>
            )}
            {categoriesQuery.data?.length === 0 && !isOwner && <p className="mt-2 text-sm text-red-700">Kategori pengeluaran belum tersedia. Minta owner menambahkan kategori.</p>}
            {showNewCategory && isOwner && (
              <div className="mt-3 flex gap-2">
                <input value={newCategoryName} onChange={(event) => setNewCategoryName(event.target.value)} maxLength={80} placeholder="Nama kategori" className="h-11 min-w-0 flex-1 rounded-xl border border-line px-3" />
                <button type="button" onClick={() => { if (newCategoryName.trim()) categoryMutation.mutate(newCategoryName.trim()); }} disabled={categoryMutation.isPending || !newCategoryName.trim()} className="h-11 rounded-xl bg-brand px-4 font-bold text-white disabled:opacity-50">Tambah</button>
              </div>
            )}
          </div>

          <label className="mt-5 block text-sm font-bold text-muted">
            Catatan
            <input value={description} onChange={(event) => setDescription(event.target.value)} maxLength={500} placeholder="Contoh: gula 5 kg" className="mt-2 h-14 w-full rounded-2xl border border-line px-4 text-base text-ink placeholder:text-muted/70" />
          </label>

          {!editing && <label className="mt-5 block text-sm font-bold text-muted">
            Foto nota <span className="font-normal">(opsional, maks. 5 MB)</span>
            <input type="file" accept="image/*" onChange={(event) => setReceipt(event.target.files?.[0] ?? null)} className="mt-2 block min-h-11 w-full rounded-xl border border-line bg-white p-2 text-sm" />
          </label>}

          {(saveError || expensesQuery.isError || categoriesQuery.isError || receiptError) && <p role="alert" className="mt-4 rounded-xl bg-red-50 p-3 text-sm font-semibold text-red-800">{receiptError || (saveError ? apiError(saveError) : "Data pengeluaran belum bisa dimuat. Periksa koneksi lalu coba lagi.")}</p>}
          <div className="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            {editing && <button type="button" onClick={cancelEdit} className="min-h-12 rounded-xl border border-line px-6 font-bold">Batal edit</button>}
            <button disabled={!categoryId || !amount || busy} className="min-h-12 rounded-xl bg-brand px-8 font-extrabold text-white disabled:opacity-50">
              {busy ? "Menyimpan..." : editing ? "Simpan perubahan" : "Simpan pengeluaran"}
            </button>
          </div>
        </form>

        <section className="mt-8">
          <h2 className="border-b border-line pb-2 text-lg font-extrabold">Tercatat {date === localToday() ? "hari ini" : date}</h2>
          {expensesQuery.isLoading ? <p className="py-6 text-sm text-muted">Memuat pengeluaran...</p> : expenses.length ? (
            <ul className="divide-y divide-line">
              {expenses.map((expense) => (
                <li key={expense.id} className="flex flex-wrap items-center justify-between gap-3 py-4">
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-bold">{expense.description || expense.category.name}</p>
                    <p className="mt-1 text-sm text-muted">{expense.category.name} · {new Date(`${expense.created_at}`).toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit", timeZone: "Asia/Jakarta" })}{isOwner && expense.user ? ` · ${expense.user.name}` : ""}</p>
                    {expense.receipt_url && <button type="button" onClick={() => void openReceipt(expense.receipt_url!)} className="mt-1 inline-block text-left text-sm font-semibold text-brand">Lihat foto nota</button>}
                  </div>
                  <div className="flex items-center gap-3">
                    <p className="font-extrabold text-red-700">{formatRupiah(expense.amount)}</p>
                    {isOwner && <div className="flex gap-2">
                      <button onClick={() => startEdit(expense)} className="min-h-10 rounded-lg border border-line px-3 text-sm font-bold">Edit</button>
                      <button onClick={() => { if (window.confirm("Hapus catatan pengeluaran ini?")) deleteMutation.mutate(expense.id); }} disabled={deleteMutation.isPending} className="min-h-10 rounded-lg border border-red-200 px-3 text-sm font-bold text-red-700 disabled:opacity-50">Hapus</button>
                    </div>}
                  </div>
                </li>
              ))}
            </ul>
          ) : <p className="py-6 text-sm text-muted">Belum ada pengeluaran pada tanggal ini.</p>}
        </section>
      </main>

      <nav className="fixed inset-x-0 bottom-0 z-10 grid h-16 grid-cols-4 border-t border-line bg-white lg:hidden" aria-label="Navigasi kasir">
        <Link to="/kasir" className="grid place-items-center font-bold text-muted">Kasir</Link>
        <Link to="/expenses" className={`grid place-items-center font-bold ${location.pathname === "/expenses" ? "text-brand" : "text-muted"}`}>Pengeluaran</Link>
        <Link to="/debts" className="grid place-items-center font-bold text-muted">Bon</Link>
        <Link to="/transactions" className="grid place-items-center font-bold text-muted">Riwayat</Link>
      </nav>
    </div>
  );
}
