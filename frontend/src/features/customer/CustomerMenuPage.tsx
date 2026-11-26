import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import axios from "axios";
import { formatRupiah } from "../../lib/format";
import type { Menu } from "../../store/cart";

type MenuResponse = { data: Menu[] };

export default function CustomerMenuPage() {
  const [category, setCategory] = useState("Semua");
  const menuQuery = useQuery({
    queryKey: ["public-menus"],
    queryFn: async () => (await axios.get<MenuResponse>("/api/public/menus")).data.data,
  });
  const menus = menuQuery.data ?? [];
  const categories = useMemo(
    () => ["Semua", ...new Set(menus.map((menu) => menu.category.name))],
    [menus],
  );
  const visibleMenus = category === "Semua"
    ? menus
    : menus.filter((menu) => menu.category.name === category);

  return (
    <main className="min-h-screen bg-ground">
      <header className="bg-ink px-5 py-10 text-white">
        <div className="mx-auto max-w-5xl">
          <p className="text-sm font-bold uppercase tracking-[0.2em] text-amber-300">Warkop Pak Haji</p>
          <h1 className="mt-2 text-3xl font-extrabold sm:text-4xl">Menu kami</h1>
          <p className="mt-2 text-white/75">Lihat pilihan menu dan harga terbaru.</p>
        </div>
      </header>

      <section className="mx-auto max-w-5xl px-5 py-7">
        {menuQuery.isError ? (
          <div role="alert" className="rounded-2xl bg-white p-6 text-center">
            <p className="font-bold">Menu belum bisa dimuat.</p>
            <button onClick={() => void menuQuery.refetch()} className="mt-3 font-bold text-brand underline">Coba lagi</button>
          </div>
        ) : menuQuery.isLoading ? (
          <p className="py-10 text-center font-semibold text-muted">Memuat menu…</p>
        ) : menus.length === 0 ? (
          <p className="rounded-2xl bg-white p-8 text-center font-semibold text-muted">Belum ada menu tersedia.</p>
        ) : (
          <>
            <nav aria-label="Kategori menu" className="mb-6 flex gap-2 overflow-x-auto pb-2">
              {categories.map((name) => (
                <button
                  key={name}
                  onClick={() => setCategory(name)}
                  className={`shrink-0 rounded-full px-4 py-2 text-sm font-bold ${category === name ? "bg-brand text-white" : "border border-line bg-white text-ink"}`}
                >
                  {name}
                </button>
              ))}
            </nav>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {visibleMenus.map((menu) => (
                <article key={menu.id} className="rounded-2xl border border-line bg-white p-5 shadow-sm">
                  <p className="text-xs font-bold uppercase tracking-wide text-brand">{menu.category.name}</p>
                  <h2 className="mt-2 text-lg font-extrabold">{menu.name}</h2>
                  <p className="mt-3 text-lg font-extrabold">{formatRupiah(menu.price)}</p>
                </article>
              ))}
            </div>
          </>
        )}
      </section>
      <footer className="px-5 py-6 text-center text-sm text-muted">Silakan pesan langsung ke kasir.</footer>
    </main>
  );
}
