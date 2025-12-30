import { Route, Routes, Navigate } from 'react-router-dom';
import { Toaster } from 'sonner';
import { ProtectedRoute } from './components/protected-route';
import { AdminRoute } from './components/admin-route';
import { DashboardLayout } from './layouts/dashboard-layout';
import { ChatPage } from './pages/chat';
import { DashboardPage } from './pages/dashboard';
import { InventoryPage } from './pages/inventory';
import { LoginPage } from './pages/login';
import { RegisterPage } from './pages/register';
import { WarehousesPage } from './pages/warehouses';
import { DispatchesPage } from './pages/dispatches';
import { InventoryLogsPage } from './pages/inventory-logs';
import { DispatchLogsPage } from './pages/dispatch-logs';
import { UsersPage } from './pages/users';
import { useAuth } from './contexts/auth-context';

function DefaultRoute() {
  const { user } = useAuth();
  return user?.role === 'admin' ? <DashboardPage /> : <Navigate to="/inventory" replace />;
}

function App() {
  return (
    <>
      <Toaster position="top-right" richColors />
      <Routes>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route
          path="/"
          element={
            <ProtectedRoute>
              <DashboardLayout />
            </ProtectedRoute>
          }
        >
          <Route index element={<DefaultRoute />} />
          <Route path="inventory" element={<InventoryPage />} />
          <Route path="warehouses" element={<AdminRoute><WarehousesPage /></AdminRoute>} />
          <Route path="dispatches" element={<DispatchesPage />} />
          <Route path="logs/inventory" element={<AdminRoute><InventoryLogsPage /></AdminRoute>} />
          <Route path="logs/dispatch" element={<AdminRoute><DispatchLogsPage /></AdminRoute>} />
          <Route path="users" element={<AdminRoute><UsersPage /></AdminRoute>} />
          <Route path="chat" element={<ChatPage />} />
        </Route>
      </Routes>
    </>
  );
}

export default App;
