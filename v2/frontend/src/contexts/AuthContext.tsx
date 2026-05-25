import { createContext, useCallback, useContext, useEffect, useState, type ReactNode } from 'react';
import { api, auth } from '../lib/api';

interface User {
  id: number;
  username: string;
  role: 'admin' | 'supervisor' | 'operator';
  fullName?: string | null;
  email?: string | null;
  hasAvatar?: boolean;
  avatarUpdatedAt?: string | null;
}

interface AuthState {
  user: User | null;
  loading: boolean;
  login: (u: string, p: string) => Promise<void>;
  logout: () => void;
  /** Re-fetch del user actual desde el backend (después de update profile/avatar) */
  refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthState | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  const fetchMe = useCallback(async () => {
    const r = await api.get('/auth/me');
    setUser(r.data.user);
  }, []);

  useEffect(() => {
    const token = auth.getToken();
    if (!token) { setLoading(false); return; }
    fetchMe()
      .catch(() => auth.clearToken())
      .finally(() => setLoading(false));
  }, [fetchMe]);

  const login = async (username: string, password: string) => {
    const r = await api.post('/auth/login', { username, password });
    auth.saveToken(r.data.token);
    setUser(r.data.user);
  };

  const logout = () => {
    auth.clearToken();
    setUser(null);
    window.location.href = '/login';
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, refreshUser: fetchMe }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth fuera de AuthProvider');
  return ctx;
}
