import { useState, type FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import axios from "axios";
import { useNavigate } from "react-router-dom";
import { api } from "../../lib/api";
import { formatRupiah } from "../../lib/format";
import type { Menu } from "../../store/cart";

type Category = { id: number; name: string };
type Collection<T> = { data: T[] };
type MenuDraft = {
  id: number | null;
  category_id: number;
  name: string;
  price: string;
  is_active: boolean;
};
type MenuData = Omit<MenuDraft, "id" | "price"> & { price: number };
type SaveInput = { id: number | null; data: MenuData };

export default function MenusPage() {
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [draft, setDraft] = useState<MenuDraft | null>(null);

  const menusQuery = useQuery({
    queryKey: ["menus", "manage"],
    queryFn: async () => (await api.get<Collection<Menu>>("/menus", { params: { include_inactive: true } })).data.data,
  });
  const categoriesQuery = useQuery({
    queryKey: ["categories"],
    queryFn: async () => (await api.get<Collection<Category>>("/categories")).data.data,
  });
  const menus = menusQuery.data ?? [];
  const categories = categoriesQuery.data ?? [];

  const saveMenu = useMutation({
    mutationFn: async ({ id, data }: SaveInput) => {
      if (id) return api.put(`/menus/${id}`, data);
      return api.post("/menus", data);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ["menus"] });
      setDraft(null);
    },
  });

  function addMenu() {
    setDraft({ id: null, category_id: categories[0]?.id ?? 0, name: "", price: "", is_active: true });
    saveMenu.reset();
  }

  function editMenu(menu: Menu) {
    setDraft({
      id: menu.id,
      category_id: menu.category.id,
      name: menu.name,
      price: String(menu.price),
      is_active: menu.is_active,
    });
    saveMenu.reset();
  }

  function toggleMenu(menu: Menu) {
    saveMenu.mutate({
      id: menu.id,
      data: {
        category_id: menu.category.id,
        name: menu.name,
        price: menu.price,
        is_active: !menu.is_active,
      },
    });
  }

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!draft || !draft.category_id || !draft.price) return;
    saveMenu.mutate({
      id: draft.id,
      data: {
        category_id: draft.category_id,
        name: draft.name.trim(),
        price: Number(draft.price),
        is_active: draft.is_active,
      },
    });
  }

  const saveError = axios.isAxiosError(saveMenu.error)
    ? saveMenu.error.response?.data?.message ?? "Menu tidak dapat disimpan."
    : saveMenu.error instanceof Error ? saveMenu.error.message : null;

  return (
    <main className="mx-auto min-h-screen max-w-6xl px-5 py-6 lg:px-10 lg:py-10">
      <header className="flex items-center justify-between gap-4">
        <div>
          <button onClick={() => navigate("/")} className="h-10 rounded-lg pr-3 text-sm font-bold text-brand">← Dashboard</button>
          <h1 className="mt-2 text-2xl font-extrabold lg:text-3xl">Menu & harga</h1>
          <p className="mt-1 text-sm text-muted">Perubahan menu hanya bisa dilakukan owner.</p>
        </div>
        <button
          onClick={addMenu}
          disabled={!categories.length || categoriesQuery.isLoading}
          className="h-12 shrink-0 rounded-2xl bg-brand px-4 font-extrabold text-white disabled:opacity-50"
        >
          + Menu
        </button>
      </header>

      {categoriesQuery.isError && <p role="alert" className="mt-5 rounded-xl bg-red-50 p-4 text-sm font-semibold text-red-800">Kategori menu gagal dimuat.</p>}
      {!categoriesQuery.isLoading && !categoriesQuery.isError && categories.length === 0 && (
        <p className="mt-5 rounded-xl bg-amber-50 p-4 text-sm font-semibold text-amber-900">Belum ada kategori menu untuk dipilih.</p>
      )}

      {draft && (
        <form onSubmit={submit} className="mt-6 rounded-3xl border border-line bg-white p-5 lg:p-6">
          <div className="flex items-center justify-between gap-4">
            <h2 className="text-lg font-extrabold">{draft.id ? "Ubah menu" : "Tambah menu"}</h2>
            <button type="button" onClick={() => setDraft(null)} className="h-10 rounded-lg px-3 font-bold text-muted">Tutup</button>
          </div>
          <div className="mt-4 grid gap-4 sm:grid-cols-2">
            <label className="text-sm font-bold text-muted">
              Nama menu
              <input
                required
                maxLength={100}
                value={draft.name}
                onChange={(event) => setDraft({ ...draft, name: event.target.value })}
                className="mt-1.5 h-12 w-full rounded-xl border border-line px-3 text-base font-semibold text-ink"
              />
            </label>
            <label className="text-sm font-bold text-muted">
              Kategori
              <select
                required
                value={draft.category_id}
                onChange={(event) => setDraft({ ...draft, category_id: Number(event.target.value) })}
                className="mt-1.5 h-12 w-full rounded-xl border border-line bg-white px-3 text-base font-semibold text-ink"
              >
                {categories.map((category) => <option key={category.id} value={category.id}>{category.name}</option>)}
              </select>
            </label>
            <label className="text-sm font-bold text-muted">
              Harga
              <div className="mt-1.5 flex h-12 items-center gap-2 rounded-xl border border-line px-3 text-base font-semibold text-ink">
                <span className="text-muted">Rp</span>
                <input
                  required
                  inputMode="numeric"
                  value={draft.price}
                  onChange={(event) => setDraft({ ...draft, price: event.target.value.replace(/\D/g, "") })}
                  className="min-w-0 flex-1 bg-transparent outline-none"
                />
              </div>
            </label>
            <label className="flex min-h-12 items-center gap-3 self-end text-sm font-bold text-ink">
              <input
                type="checkbox"
                checked={draft.is_active}
                onChange={(event) => setDraft({ ...draft, is_active: event.target.checked })}
                className="h-5 w-5 accent-brand"
              />
              Aktif dijual
            </label>
          </div>
          {saveError && <p role="alert" className="mt-4 rounded-xl bg-red-50 p-3 text-sm font-semibold text-red-800">{saveError}</p>}
          <button disabled={saveMenu.isPending || !draft.name.trim() || !draft.price} className="mt-5 h-12 rounded-xl bg-brand px-5 font-extrabold text-white disabled:opacity-50">
            {saveMenu.isPending ? "Menyimpan..." : "Simpan menu"}
          </button>
        </form>
      )}

      <section className="mt-6 space-y-3" aria-label="Daftar menu">
        {menusQuery.isLoading && <p className="rounded-2xl border border-line bg-white p-5 text-muted">Memuat menu...</p>}
        {menusQuery.isError && (
          <div role="alert" className="rounded-2xl border border-line bg-white p-5">
            <p className="font-bold">Daftar menu gagal dimuat.</p>
            <button onClick={() => void menusQuery.refetch()} className="mt-3 h-10 rounded-xl bg-brand px-4 font-bold text-white">Coba lagi</button>
          </div>
        )}
        {!menusQuery.isLoading && !menusQuery.isError && menus.length === 0 && (
          <p className="rounded-2xl border border-line bg-white p-8 text-center text-muted">Belum ada menu.</p>
        )}
        {menus.map((menu) => (
          <article key={menu.id} className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-line bg-white p-4">
            <div className="min-w-0">
              <div className="flex flex-wrap items-center gap-2">
                <h2 className="font-extrabold">{menu.name}</h2>
                <span className={`rounded-full px-2.5 py-1 text-xs font-bold ${menu.is_active ? "bg-emerald-50 text-emerald-800" : "bg-slate-100 text-muted"}`}>
                  {menu.is_active ? "Aktif" : "Nonaktif"}
                </span>
              </div>
              <p className="mt-1 text-sm text-muted">{menu.category.name} · {formatRupiah(menu.price)}</p>
            </div>
            <div className="flex gap-2">
              <button onClick={() => editMenu(menu)} className="h-11 rounded-xl border border-line px-4 text-sm font-bold hover:border-brand">Ubah</button>
              <button
                onClick={() => toggleMenu(menu)}
                disabled={saveMenu.isPending}
                className="h-11 rounded-xl border border-line px-4 text-sm font-bold hover:border-brand disabled:opacity-50"
              >
                {menu.is_active ? "Nonaktifkan" : "Aktifkan"}
              </button>
            </div>
          </article>
        ))}
      </section>
    </main>
  );
}
