import { Link, Outlet, useLocation } from 'react-router-dom';
import { LayoutDashboard, Package, MessageSquare, LogOut, Warehouse, Truck, ClipboardList, Users } from 'lucide-react';
import { useAuth } from '@/contexts/auth-context';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const adminNavItems = [
  { href: '/', label: 'Dashboard', icon: LayoutDashboard },
  { href: '/inventory', label: 'Inventory', icon: Package },
  { href: '/warehouses', label: 'Warehouses', icon: Warehouse },
  { href: '/dispatches', label: 'Dispatches', icon: Truck },
  { href: '/users', label: 'Users', icon: Users },
  { href: '/logs/inventory', label: 'Inventory Logs', icon: ClipboardList },
  { href: '/logs/dispatch', label: 'Dispatch Logs', icon: ClipboardList },
  { href: '/chat', label: 'Chat', icon: MessageSquare },
];

const userNavItems = [
  { href: '/inventory', label: 'Inventory', icon: Package },
  { href: '/dispatches', label: 'Dispatches', icon: Truck },
  { href: '/chat', label: 'Chat', icon: MessageSquare },
];

export function DashboardLayout() {
  const { user, logout } = useAuth();
  const location = useLocation();

  const navItems = user?.role === 'admin' ? adminNavItems : userNavItems;

  return (
    <div className="flex h-screen">
      {/* Sidebar */}
      <aside className="w-64 border-r bg-sidebar">
        <div className="flex h-14 items-center justify-between border-b px-4">
          <h1 className="text-lg font-semibold">Inventory System</h1>
          {user?.role === 'admin' && (
            <span className="rounded bg-primary px-2 py-0.5 text-xs text-primary-foreground">Admin</span>
          )}
        </div>
        <nav className="flex flex-col gap-1 p-4">
          {navItems.map((item) => {
            const Icon = item.icon;
            const isActive = location.pathname === item.href;
            return (
              <Link
                key={item.href}
                to={item.href}
                className={cn(
                  'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                  isActive
                    ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                    : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground'
                )}
              >
                <Icon className="h-4 w-4" />
                {item.label}
              </Link>
            );
          })}
        </nav>
        <div className="absolute bottom-0 w-64 border-t p-4">
          <div className="mb-2 text-sm text-muted-foreground">{user?.email}</div>
          <Button variant="outline" size="sm" className="w-full" onClick={logout}>
            <LogOut className="mr-2 h-4 w-4" />
            Logout
          </Button>
        </div>
      </aside>

      {/* Main content */}
      <main className="flex-1 overflow-auto">
        <div className="p-6">
          <Outlet />
        </div>
      </main>
    </div>
  );
}
