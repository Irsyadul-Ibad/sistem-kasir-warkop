import { create } from "zustand";

export type Menu = {
  id: number;
  name: string;
  price: number;
  is_active: boolean;
  category: { id: number; name: string };
};

type Line = { menu: Menu; qty: number; note: string };

type CartState = {
  lines: Line[];
  add: (menu: Menu) => void;
  decrease: (menuId: number) => void;
  updateNote: (menuId: number, note: string) => void;
  clear: () => void;
};

export const useCart = create<CartState>((set) => ({
  lines: [],
  add: (menu) =>
    set((s) => {
      const found = s.lines.find((l) => l.menu.id === menu.id);
      return found
        ? { lines: s.lines.map((l) => (l.menu.id === menu.id ? { ...l, qty: l.qty + 1 } : l)) }
        : { lines: [...s.lines, { menu, qty: 1, note: "" }] };
    }),
  decrease: (menuId) =>
    set((s) => ({
      lines: s.lines
        .map((l) => (l.menu.id === menuId ? { ...l, qty: l.qty - 1 } : l))
        .filter((l) => l.qty > 0),
    })),
  updateNote: (menuId, note) =>
    set((s) => ({
      lines: s.lines.map((line) => line.menu.id === menuId ? { ...line, note } : line),
    })),
  clear: () => set({ lines: [] }),
}));

export const cartTotal = (lines: Line[]) =>
  lines.reduce((sum, l) => sum + l.menu.price * l.qty, 0);
