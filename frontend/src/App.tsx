import { Navigate, Route, Routes } from "react-router-dom";
import type { ReactNode } from "react";
import { useAuth } from "./store/auth";
import LoginPage from "./features/auth/LoginPage";
import AuthCompletePage from "./features/auth/AuthCompletePage";
import HomePage from "./features/home/HomePage";
import MenusPage from "./features/owner/MenusPage";
import ExpensesPage from "./features/expenses/ExpensesPage";
import DebtsPage from "./features/debts/DebtsPage";
import ShiftPage from "./features/shifts/ShiftPage";
import KasirPage from "./features/pos/KasirPage";
import { TransactionDetailPage, TransactionsPage } from "./features/transactions/TransactionsPage";
import CustomerMenuPage from "./features/customer/CustomerMenuPage";

function RequireAuth({ children }: { children: ReactNode }) {
  const token = useAuth((state) => state.token);
  return token ? <>{children}</> : <Navigate to="/login" replace />;
}

function RequireOwner({ children }: { children: ReactNode }) {
  const role = useAuth((state) => state.user?.role);
  return role === "owner" ? <>{children}</> : <Navigate to="/" replace />;
}

function RoleHome() {
  const role = useAuth((state) => state.user?.role);
  return role === "owner" ? <HomePage /> : <KasirPage />;
}

export default function App() {
  return (
    <Routes>
      <Route path="/pelanggan" element={<CustomerMenuPage />} />
      <Route path="/login" element={<LoginPage />} />
      <Route path="/auth/complete" element={<AuthCompletePage />} />
      <Route
        path="/"
        element={
          <RequireAuth>
            <RoleHome />
          </RequireAuth>
        }
      />
      <Route path="/kasir" element={<RequireAuth><KasirPage /></RequireAuth>} />
      <Route path="/transactions" element={<RequireAuth><TransactionsPage /></RequireAuth>} />
      <Route path="/transactions/:id" element={<RequireAuth><TransactionDetailPage /></RequireAuth>} />
      <Route path="/expenses" element={<RequireAuth><ExpensesPage /></RequireAuth>} />
      <Route path="/debts" element={<RequireAuth><DebtsPage /></RequireAuth>} />
      <Route path="/shift" element={<RequireAuth><ShiftPage /></RequireAuth>} />
      <Route path="/owner/menus" element={<RequireAuth><RequireOwner><MenusPage /></RequireOwner></RequireAuth>} />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}
