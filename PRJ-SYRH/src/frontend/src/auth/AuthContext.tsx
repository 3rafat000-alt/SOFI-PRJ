import { createContext, useContext, useState, useEffect, type ReactNode } from 'react';
import { login as apiLogin, register as apiRegister, agencyRegister as apiAgencyRegister, logout as apiLogout, fetchMe, type AuthUser, type AgencyRegisterData } from '../api/auth';

interface AuthContextType {
  user: AuthUser | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<AuthUser>;
  register: (data: { name: string; email: string; password: string; password_confirmation: string; phone?: string; locale?: string }) => Promise<AuthUser>;
  agencyRegister: (data: AgencyRegisterData) => Promise<AuthUser>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      fetchMe().then(setUser).catch(() => {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_user');
      }).finally(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, []);

  const login = async (email: string, password: string) => {
    const res = await apiLogin(email, password);
    localStorage.setItem('auth_token', res.data.token);
    localStorage.setItem('auth_user', JSON.stringify(res.data.user));
    setUser(res.data.user);
    return res.data.user;
  };

  const register = async (data: { name: string; email: string; password: string; password_confirmation: string; phone?: string; locale?: string }) => {
    const res = await apiRegister(data);
    localStorage.setItem('auth_token', res.data.token);
    localStorage.setItem('auth_user', JSON.stringify(res.data.user));
    setUser(res.data.user);
    return res.data.user;
  };

  const agencyRegister = async (data: AgencyRegisterData) => {
    const res = await apiAgencyRegister(data);
    localStorage.setItem('auth_token', res.data.token);
    localStorage.setItem('auth_user', JSON.stringify(res.data.user));
    setUser(res.data.user);
    return res.data.user;
  };

  const logout = async () => {
    try { await apiLogout(); } catch {}
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_user');
    setUser(null);
  };

  const refreshUser = async () => {
    const u = await fetchMe();
    setUser(u);
    localStorage.setItem('auth_user', JSON.stringify(u));
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, register, agencyRegister, logout, refreshUser }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
