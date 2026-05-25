import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';

const THEME_KEY = 'hznflow_theme';
type Theme = 'light' | 'dark';

interface ThemeState {
  theme: Theme;
  toggle: () => void;
}

const ThemeContext = createContext<ThemeState | null>(null);

export function ThemeProvider({ children }: { children: ReactNode }) {
  const [theme, setTheme] = useState<Theme>(() => (localStorage.getItem(THEME_KEY) as Theme) || 'light');

  useEffect(() => {
    const root = document.documentElement;
    const body = document.body;
    root.classList.remove('light', 'dark');
    body.classList.remove('light', 'dark');
    root.classList.add(theme);
    body.classList.add(theme);
    localStorage.setItem(THEME_KEY, theme);
  }, [theme]);

  const toggle = () => setTheme((t) => (t === 'dark' ? 'light' : 'dark'));

  return <ThemeContext.Provider value={{ theme, toggle }}>{children}</ThemeContext.Provider>;
}

export function useTheme() {
  const ctx = useContext(ThemeContext);
  if (!ctx) throw new Error('useTheme fuera de ThemeProvider');
  return ctx;
}
