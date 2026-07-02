import { Navigate } from 'react-router-dom';
import { useAuth } from '../../auth/AuthContext';

interface Props {
  children: React.ReactNode;
  roles?: string[];
}

export default function ProtectedRoute({ children, roles }: Props) {
  const { user, loading } = useAuth();

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-beige/40">
        <div className="w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin" />
      </div>
    );
  }

  if (!user) return <Navigate to="/login" replace />;

  if (roles && roles.length > 0) {
    const userRoles = (user as any)?.roles?.map((r: any) => r.name) || [];
    const hasRole = roles.some(r => userRoles.includes(r));
    if (!hasRole) {
      // Redirect to appropriate dashboard based on their actual role
      if (userRoles.includes('admin')) return <Navigate to="/admin" replace />;
      if (userRoles.includes('agency')) return <Navigate to="/dashboard" replace />;
      return <Navigate to="/user/dashboard" replace />;
    }
  }

  return <>{children}</>;
}
