import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './contexts/AuthContext';
import { ThemeProvider } from './contexts/ThemeContext';
import Login from './pages/Login';
import Layout from './components/Layout';
import Dashboard from './pages/Dashboard';
import Agentes from './pages/Agentes';
import Extensiones from './pages/Extensiones';
import Configuracion from './pages/Configuracion';
import Perfil from './pages/Perfil';
import Hotdesking from './pages/Hotdesking';
import Llamadas from './pages/Llamadas';
import Colas from './pages/Colas';
import Grupos from './pages/Grupos';
import CDR from './pages/CDR';
import Reportes from './pages/Reportes';
import IVR from './pages/IVR';

function RequireAuth({ children }: { children: JSX.Element }) {
  const { user, loading } = useAuth();
  if (loading) return <div className="p-6">Cargando...</div>;
  if (!user) return <Navigate to="/login" replace />;
  return children;
}

export default function App() {
  return (
    <ThemeProvider>
      <BrowserRouter>
        <AuthProvider>
          <Routes>
            <Route path="/login" element={<Login />} />
            <Route element={<RequireAuth><Layout /></RequireAuth>}>
              <Route path="/" element={<Dashboard />} />
              <Route path="/extensiones" element={<Extensiones />} />
              <Route path="/agentes" element={<Agentes />} />
              <Route path="/hotdesking" element={<Hotdesking />} />
              <Route path="/colas" element={<Colas />} />
              <Route path="/grupos" element={<Grupos />} />
              <Route path="/llamadas" element={<Llamadas />} />
              <Route path="/cdr" element={<CDR />} />
              <Route path="/reportes" element={<Reportes />} />
              <Route path="/ivr" element={<IVR />} />
              <Route path="/configuracion" element={<Configuracion />} />
              <Route path="/perfil" element={<Perfil />} />
            </Route>
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </AuthProvider>
      </BrowserRouter>
    </ThemeProvider>
  );
}
