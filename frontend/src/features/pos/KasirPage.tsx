import { useMemo, useRef, useState } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import axios from "axios";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { api } from "../../lib/api";
import { formatRupiah } from "../../lib/format";
import { useAuth } from "../../store/auth";
import { cartTotal, useCart, type Menu } from "../../store/cart";

type PaymentMethod = "cash" | "qris" | "transfer" | "debt";
type Transaction = {
  invoice_no?: string;
  total?: number;
  amount_paid?: number;
  change_amount?: number;
  payment_method?: PaymentMethod;
  status?: string;
  customer?: { name: string; phone: string | null } | null;
  table_label?: string | null;
  paid_at?: string | null;
  items?: { id: number; menu_name_snapshot: string; qty: number; line_total: number; note?: string | null }[];
};
type TransactionResponse = { data: Transaction };
type CheckoutPayload = {
  client_uuid: string;
  payment_method: PaymentMethod;
  table_label?: string;
  amount_paid?: number;
  customer_name?: string;
  customer_phone?: string;
  items: { menu_id: number; qty: number; note?: string }[];
};

const paymentMethods: { id: PaymentMethod; label: string }[] = [
  { id: "cash", label: "Tunai" },
  { id: "qris", label: "QRIS" },
  { id: "transfer", label: "Transfer" },
  { id: "debt", label: "Bon" },
];
const emptyMenus: Menu[] = [];

function errorMessage(error: unknown) {
  if (axios.isAxiosError(error)) {
    return error.response?.data?.message ?? "Tidak bisa terhubung ke server.";
  }
  return error instanceof Error ? error.message : "Transaksi gagal disimpan.";
}

export default function KasirPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const user = useAuth((state) => state.user);
  const logout = useAuth((state) => state.logout);
  const { lines, add, decrease, updateNote, clear } = useCart();
  const [category, setCategory] = useState("Semua");
  const [search, setSearch] = useState("");
  const [tableLabel, setTableLabel] = useState("");
  const [customerName, setCustomerName] = useState("");
  const [customerPhone, setCustomerPhone] = useState("");
  const [paymentMethod, setPaymentMethod] = useState<PaymentMethod>("cash");
  const [cashReceived, setCashReceived] = useState("");
  const [checkoutOpen, setCheckoutOpen] = useState(false);
  const [receipt, setReceipt] = useState<Transaction | null>(null);
  const clientUuid = useRef<string | null>(null);

  const menuQuery = useQuery({
    queryKey: ["menus"],
    queryFn: async () => (await api.get("/menus")).data.data as Menu[],
  });
  const menus = menuQuery.data ?? emptyMenus;
  const categories = useMemo(
    () => ["Semua", ...new Set(menus.map((menu) => menu.category.name))],
    [menus],
  );
  const searchTerm = search.trim().toLowerCase();
  const visibleMenus = menus.filter((menu) =>
    (category === "Semua" || menu.category.name === category)
    && menu.name.toLowerCase().includes(searchTerm),
  );
  const total = cartTotal(lines);
  const itemCount = lines.reduce((count, line) => count + line.qty, 0);
  const received = Number(cashReceived);
  const change = Math.max(0, received - total);
  const paymentSuggestions = [...new Set([
    Math.ceil(total / 50_000) * 50_000,
    Math.ceil(total / 100_000) * 100_000,
  ])].filter((amount) => amount > total);

  const transactionMutation = useMutation({
    mutationFn: async (payload: CheckoutPayload) => {
      const response = await api.post<TransactionResponse>("/transactions", payload);
      return response.data.data;
    },
    onSuccess: (savedTransaction) => {
      setReceipt(savedTransaction);
      clientUuid.current = null;
      clear();
      setTableLabel("");
      setCustomerName("");
      setCustomerPhone("");
      setCashReceived("");
      setPaymentMethod("cash");
    },
  });

  function submitTransaction() {
    if (!lines.length || transactionMutation.isPending) return;
    if (paymentMethod === "cash" && received < total) return;

    clientUuid.current ??= crypto.randomUUID();
    const payload: CheckoutPayload = {
      client_uuid: clientUuid.current,
      payment_method: paymentMethod,
      ...(tableLabel.trim() ? { table_label: tableLabel.trim() } : {}),
      ...(paymentMethod === "debt" ? {
        customer_name: customerName.trim(),
        ...(customerPhone.trim() ? { customer_phone: customerPhone.trim() } : {}),
      } : {}),
      items: lines.map(({ menu, qty, note }) => ({
        menu_id: menu.id,
        qty,
        ...(note.trim() ? { note: note.trim() } : {}),
      })),
      ...(paymentMethod === "cash" ? { amount_paid: received } : {}),
    };
    transactionMutation.mutate(payload);
  }

  function startNewTransaction() {
    setReceipt(null);
    setCheckoutOpen(false);
    transactionMutation.reset();
  }

  async function signOut() {
    try {
      await api.post("/auth/logout");
    } catch {
      // Crew tetap bisa keluar saat server sedang tidak terjangkau.
    }
    logout();
  }

  function checkoutPanel(mobile = false) {
    if (receipt) {
      return (
        <section className="receipt-print flex min-h-full flex-col bg-white p-5 lg:rounded-3xl lg:p-6">
          {mobile && (
            <button onClick={startNewTransaction} className="mb-8 h-11 self-start rounded-xl px-3 font-bold text-brand print:hidden">
              ← Kasir
            </button>
          )}
          <div className="my-auto rounded-3xl border border-line p-6 text-center">
            <div className="mx-auto grid h-14 w-14 place-items-center rounded-full bg-emerald-100 text-2xl font-black text-emerald-800">✓</div>
            <h2 className="mt-4 text-2xl font-extrabold">Transaksi tersimpan</h2>
            {receipt.invoice_no && <p className="mt-2 font-semibold text-muted">{receipt.invoice_no}</p>}
            <p className="mt-5 text-sm font-semibold text-muted">Total pembayaran</p>
            <p className="mt-1 text-3xl font-extrabold">{formatRupiah(receipt.total ?? total)}</p>
            {receipt.status === "debt" && <p className="mt-2 rounded-xl bg-amber-50 p-3 text-sm font-bold text-amber-900">Bon dicatat untuk {receipt.customer?.name ?? "pelanggan"}. Pelunasan dapat dilakukan nanti.</p>}
            {receipt.paid_at && <p className="mt-2 text-sm text-muted">{new Date(receipt.paid_at).toLocaleString("id-ID", { timeZone: "Asia/Jakarta" })}</p>}
            {receipt.table_label && <p className="mt-1 text-sm text-muted">{receipt.table_label}</p>}
            {receipt.items && (
              <div className="my-5 space-y-2 border-y border-line py-4 text-left">
                {receipt.items.map((item) => (
                  <div key={item.id} className="flex justify-between gap-3 text-sm">
                    <span>{item.qty} × {item.menu_name_snapshot}{item.note ? ` · ${item.note}` : ""}</span>
                    <span className="shrink-0 font-bold">{formatRupiah(item.line_total)}</span>
                  </div>
                ))}
              </div>
            )}
            {(receipt.change_amount ?? 0) > 0 && (
              <p className="mt-3 font-bold text-emerald-800">Kembalian {formatRupiah(receipt.change_amount ?? 0)}</p>
            )}
            <button onClick={() => window.print()} className="mt-7 h-12 w-full rounded-2xl border border-line font-extrabold print:hidden">
              Cetak struk
            </button>
            <button onClick={startNewTransaction} className="mt-2 h-12 w-full rounded-2xl bg-brand font-extrabold text-white print:hidden">
              Transaksi baru
            </button>
          </div>
        </section>
      );
    }

    return (
      <section className={`flex min-h-full flex-col bg-white p-5 lg:p-6 ${mobile ? "" : "lg:rounded-3xl"}`}>
        {mobile && (
          <button onClick={() => setCheckoutOpen(false)} className="mb-5 h-11 self-start rounded-xl px-3 font-bold text-brand">
            ← Kembali ke pesanan
          </button>
        )}
        <div className="flex items-center justify-between">
          <h2 className="text-xl font-extrabold">Pesanan</h2>
          <span className="text-sm font-semibold text-muted">{itemCount} item</span>
        </div>

        {lines.length === 0 ? (
          <div className="my-auto py-12 text-center text-muted">
            <p className="font-bold">Keranjang masih kosong</p>
            <p className="mt-1 text-sm">Pilih menu untuk mulai transaksi.</p>
          </div>
        ) : (
          <>
            <div className="mt-5 max-h-64 space-y-4 overflow-y-auto pr-1 lg:max-h-[32vh]">
              {lines.map(({ menu, qty, note }) => (
                <div key={menu.id} className="flex items-center justify-between gap-3">
                  <div className="min-w-0">
                    <p className="truncate font-bold">{menu.name}</p>
                    <p className="text-sm text-muted">{formatRupiah(menu.price)} × {qty}</p>
                    <input
                      value={note}
                      onChange={(event) => updateNote(menu.id, event.target.value)}
                      maxLength={255}
                      placeholder="Catatan (opsional)"
                      aria-label={`Catatan untuk ${menu.name}`}
                      className="mt-1 h-9 w-full rounded-lg border border-line px-2 text-sm"
                    />
                  </div>
                  <div className="flex shrink-0 items-center gap-2">
                    <button onClick={() => decrease(menu.id)} aria-label={`Kurangi ${menu.name}`} className="h-11 w-11 rounded-xl border border-line font-bold">−</button>
                    <span className="w-5 text-center font-bold">{qty}</span>
                    <button onClick={() => add(menu)} aria-label={`Tambah ${menu.name}`} className="h-11 w-11 rounded-xl bg-brand font-bold text-white">+</button>
                  </div>
                </div>
              ))}
            </div>

            <div className="mt-5 border-t border-line pt-4">
              <label htmlFor={mobile ? "table-mobile" : "table-desktop"} className="text-sm font-bold text-muted">Nomor meja (opsional)</label>
              <input
                id={mobile ? "table-mobile" : "table-desktop"}
                value={tableLabel}
                onChange={(event) => setTableLabel(event.target.value)}
                maxLength={50}
                placeholder="Contoh: Meja 3"
                className="mt-1.5 h-12 w-full rounded-xl border border-line bg-white px-3 font-semibold"
              />
              <div className="flex items-center justify-between gap-4">
                <span className="text-lg font-bold">Total</span>
                <span className="text-2xl font-extrabold">{formatRupiah(total)}</span>
              </div>

              <div className="mt-5 grid grid-cols-2 gap-2">
                {paymentMethods.map((method) => (
                  <button
                    key={method.id}
                    onClick={() => setPaymentMethod(method.id)}
                    aria-pressed={paymentMethod === method.id}
                    className={`h-12 rounded-2xl border font-bold transition-colors ${paymentMethod === method.id ? "border-brand bg-brand text-white" : "border-line bg-white hover:border-brand"}`}
                  >
                    {method.label}
                  </button>
                ))}
              </div>

              {paymentMethod === "cash" ? (
                <div className="mt-5">
                  <label htmlFor={mobile ? "cash-mobile" : "cash-desktop"} className="text-sm font-bold text-muted">Uang diterima</label>
                  <div className="mt-1.5 flex h-14 items-center rounded-2xl border-2 border-brand bg-white px-4">
                    <span className="mr-2 font-bold text-muted">Rp</span>
                    <input
                      id={mobile ? "cash-mobile" : "cash-desktop"}
                      type="text"
                      inputMode="numeric"
                      maxLength={12}
                      value={cashReceived}
                      onChange={(event) => setCashReceived(event.target.value.replace(/\D/g, ""))}
                      placeholder="0"
                      className="min-w-0 flex-1 bg-transparent text-lg font-extrabold outline-none"
                    />
                  </div>
                  <div className="mt-2 flex flex-wrap gap-2">
                    <button onClick={() => setCashReceived(String(total))} className="h-10 rounded-full border border-line px-4 text-sm font-bold">Uang pas</button>
                    {paymentSuggestions.map((amount) => (
                      <button key={amount} onClick={() => setCashReceived(String(amount))} className="h-10 rounded-full border border-line px-4 text-sm font-bold">{formatRupiah(amount)}</button>
                    ))}
                  </div>
                  <div className={`mt-4 flex items-center justify-between gap-3 rounded-2xl px-4 py-3 ${received >= total ? "bg-emerald-50 text-emerald-800" : "bg-amber-50 text-amber-900"}`} aria-live="polite">
                    <span className="font-bold">{received >= total ? "Kembalian" : "Kurang"}</span>
                    <span className="text-lg font-extrabold">{formatRupiah(received >= total ? change : total - received)}</span>
                  </div>
                </div>
              ) : paymentMethod === "debt" ? (
                <div className="mt-5 grid gap-3 sm:grid-cols-2">
                  <label className="text-sm font-bold text-muted">
                    Nama pelanggan
                    <input required value={customerName} onChange={(event) => setCustomerName(event.target.value)} maxLength={120} placeholder="Nama peminjam" className="mt-1.5 h-12 w-full rounded-xl border border-line bg-white px-3 font-semibold text-ink" />
                  </label>
                  <label className="text-sm font-bold text-muted">
                    Nomor telepon (opsional)
                    <input value={customerPhone} onChange={(event) => setCustomerPhone(event.target.value)} maxLength={30} inputMode="tel" placeholder="08..." className="mt-1.5 h-12 w-full rounded-xl border border-line bg-white px-3 font-semibold text-ink" />
                  </label>
                </div>
              ) : (
                <p className="mt-4 rounded-2xl bg-slate-50 p-4 text-sm text-muted">
                  Pembayaran {paymentMethods.find((method) => method.id === paymentMethod)?.label} dicatat setelah diterima.
                </p>
              )}
            </div>

            {transactionMutation.error && (
              <p role="alert" className="mt-4 rounded-2xl bg-red-50 p-3 text-sm font-semibold text-red-800">
                {errorMessage(transactionMutation.error)}
              </p>
            )}

            <button
              onClick={submitTransaction}
              disabled={transactionMutation.isPending || (paymentMethod === "cash" && received < total) || (paymentMethod === "debt" && !customerName.trim())}
              className="mt-5 h-14 w-full rounded-2xl bg-brand text-base font-extrabold text-white disabled:cursor-not-allowed disabled:opacity-50"
            >
              {transactionMutation.isPending ? "Menyimpan..." : "Simpan & kirim struk"}
            </button>
          </>
        )}
      </section>
    );
  }

  return (
    <div className="min-h-screen lg:grid lg:grid-cols-[minmax(0,1fr)_28rem]">
      <main className="min-w-0 px-5 pt-6 pb-36 lg:px-10 lg:py-8">
        <header className="mb-6 flex items-center justify-between gap-4">
          <div>
            <p className="text-sm font-semibold text-muted">Kasir · {user?.name ?? "Crew"}</p>
            <h1 className="text-2xl font-extrabold lg:text-3xl">Warkop Pak Haji</h1>
          </div>
          <div className="flex items-center gap-2">
            <Link to="/shift" className="grid h-11 place-items-center rounded-xl border border-line bg-white px-3 text-sm font-bold hover:border-brand">Shift</Link>
            {user?.role === "owner" && (
              <button onClick={() => navigate("/")} className="h-11 rounded-xl border border-line bg-white px-3 text-sm font-bold hover:border-brand">
                Panel owner
              </button>
            )}
            <button onClick={() => void signOut()} className="h-11 rounded-xl border border-line bg-white px-4 text-sm font-bold hover:border-brand">
              Keluar
            </button>
          </div>
        </header>

        <label className="mb-4 flex h-12 items-center gap-3 rounded-2xl border border-line bg-white px-4">
          <span aria-hidden="true" className="text-lg text-muted">⌕</span>
          <input
            type="search"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder="Cari menu..."
            aria-label="Cari menu"
            className="min-w-0 flex-1 bg-transparent font-semibold outline-none"
          />
        </label>

        <nav aria-label="Kategori menu" className="mb-5 flex gap-2 overflow-x-auto pb-1">
          {categories.map((name) => (
            <button
              key={name}
              onClick={() => setCategory(name)}
              aria-pressed={category === name}
              className={`h-11 shrink-0 rounded-full border px-5 font-bold transition-colors ${category === name ? "border-brand bg-brand text-white" : "border-line bg-white hover:border-brand"}`}
            >
              {name}
            </button>
          ))}
        </nav>

        {menuQuery.isLoading && (
          <div className="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-4" aria-label="Memuat menu">
            {Array.from({ length: 6 }, (_, index) => <div key={index} className="h-44 animate-pulse rounded-2xl border border-line bg-white" />)}
          </div>
        )}
        {menuQuery.isError && (
          <div role="alert" className="rounded-2xl border border-line bg-white p-6 text-center">
            <p className="font-bold">Menu belum bisa dimuat.</p>
            <button onClick={() => void menuQuery.refetch()} className="mt-3 h-11 rounded-xl bg-brand px-4 font-bold text-white">Coba lagi</button>
          </div>
        )}
        {!menuQuery.isLoading && !menuQuery.isError && visibleMenus.length === 0 && (
          <div className="rounded-2xl border border-line bg-white p-8 text-center text-muted">
            <p className="font-bold">Menu belum tersedia</p>
            <p className="mt-1 text-sm">Menu aktif akan muncul di sini.</p>
          </div>
        )}
        {visibleMenus.length > 0 && (
          <div className="grid grid-cols-2 gap-3 lg:grid-cols-3 lg:gap-4 xl:grid-cols-4">
            {visibleMenus.map((menu) => {
              const qty = lines.find((line) => line.menu.id === menu.id)?.qty ?? 0;
              return (
                <article key={menu.id} className="flex min-h-44 flex-col rounded-2xl border border-line bg-white p-3 lg:min-h-56 lg:p-4">
                  <div aria-hidden="true" className="h-14 rounded-xl bg-slate-100 lg:h-16" />
                  <h2 className="mt-3 line-clamp-2 font-bold lg:text-lg">{menu.name}</h2>
                  <div className="mt-auto flex items-center justify-between gap-2 pt-2">
                    <span className="text-sm font-semibold text-muted lg:text-base">{formatRupiah(menu.price)}</span>
                    {qty === 0 ? (
                      <button onClick={() => add(menu)} aria-label={`Tambah ${menu.name}`} className="h-11 w-11 shrink-0 rounded-xl bg-brand text-xl font-bold text-white">+</button>
                    ) : (
                      <div className="flex items-center gap-1">
                        <button onClick={() => decrease(menu.id)} aria-label={`Kurangi ${menu.name}`} className="h-11 w-10 rounded-xl border border-line font-bold">−</button>
                        <span className="w-6 text-center font-extrabold text-brand">{qty}</span>
                        <button onClick={() => add(menu)} aria-label={`Tambah ${menu.name}`} className="h-11 w-10 rounded-xl bg-brand font-bold text-white">+</button>
                      </div>
                    )}
                  </div>
                </article>
              );
            })}
          </div>
        )}
      </main>

      <aside className="hidden border-l border-line bg-slate-50 p-4 lg:block">
        <div className="sticky top-4 h-[calc(100vh-2rem)] overflow-y-auto">
          {checkoutPanel()}
        </div>
      </aside>

      {itemCount > 0 && (
        <button
          onClick={() => setCheckoutOpen(true)}
          className="fixed bottom-20 left-1/2 z-20 flex h-14 w-[calc(100%-2.5rem)] max-w-lg -translate-x-1/2 items-center justify-between rounded-2xl bg-ink px-5 font-extrabold text-white shadow-lg lg:hidden"
        >
          <span>{itemCount} item</span>
          <span>Bayar · {formatRupiah(total)}</span>
        </button>
      )}

      {checkoutOpen && (
        <div role="dialog" aria-modal="true" aria-labelledby="mobile-checkout-title" className="fixed inset-0 z-30 overflow-y-auto bg-ground lg:hidden">
          <div className="min-h-full">
            <span id="mobile-checkout-title" className="sr-only">Pembayaran</span>
            {checkoutPanel(true)}
          </div>
        </div>
      )}

      <nav className="fixed inset-x-0 bottom-0 z-10 grid h-16 grid-cols-4 border-t border-line bg-white lg:hidden" aria-label="Navigasi kasir">
        <Link to="/kasir" className={`grid place-items-center font-bold ${location.pathname === "/" || location.pathname === "/kasir" ? "text-brand" : "text-muted"}`}>Kasir</Link>
        <Link to="/expenses" className="grid place-items-center font-bold text-muted">Pengeluaran</Link>
        <Link to="/debts" className="grid place-items-center font-bold text-muted">Bon</Link>
        <Link to="/transactions" className={`grid place-items-center font-bold ${location.pathname.startsWith("/transactions") ? "text-brand" : "text-muted"}`}>Riwayat</Link>
      </nav>
    </div>
  );
}
