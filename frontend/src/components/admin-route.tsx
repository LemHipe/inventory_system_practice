import { Navigate } from 'react-router-dom';
import { useAuth } from '@/contexts/auth-context';

interface AdminRouteProps {
  children: React.ReactNode;
}

export function AdminRoute({ children }: AdminRouteProps) {
  const { user, isLoading } = useAuth();

  if (isLoading) {
    return (
      <div className="flex h-screen items-center justify-center">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
      </div>
    );
  }

  if (user?.role !== 'admin') {
    return <Navigate to="/inventory" replace />;
  }

  return <>{children}</>;
}
