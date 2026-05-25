import { useEffect, useRef, useState } from 'react';
import { User, Save, KeyRound, Loader2, ShieldCheck, Calendar, Clock, Camera, Trash2 } from 'lucide-react';
import { api } from '../lib/api';
import { useAuth } from '../contexts/AuthContext';
import { Card, CardContent } from '../components/ui/card';
import { Badge } from '../components/ui/badge';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { Label } from '../components/ui/label';
import { Separator } from '../components/ui/separator';
import { UserAvatar, clearAvatarCache } from '../components/UserAvatar';

interface MeUser {
  id: number;
  username: string;
  role: 'admin' | 'supervisor' | 'operator';
  fullName: string | null;
  email: string | null;
  lastLoginAt: string | null;
  createdAt: string;
  hasAvatar?: boolean;
  avatarUpdatedAt?: string | null;
}

export default function Perfil() {
  const { refreshUser } = useAuth();
  const [me, setMe] = useState<MeUser | null>(null);
  const [loading, setLoading] = useState(true);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // Editar datos
  const [fullName, setFullName] = useState('');
  const [email, setEmail] = useState('');
  const [savingProfile, setSavingProfile] = useState(false);
  const [profileMsg, setProfileMsg] = useState<{ kind: 'ok' | 'err'; text: string } | null>(null);

  // Avatar
  const [uploadingAvatar, setUploadingAvatar] = useState(false);
  const [avatarMsg, setAvatarMsg] = useState<string | null>(null);

  // Cambiar password
  const [currentPwd, setCurrentPwd] = useState('');
  const [newPwd, setNewPwd] = useState('');
  const [confirmPwd, setConfirmPwd] = useState('');
  const [savingPwd, setSavingPwd] = useState(false);
  const [pwdMsg, setPwdMsg] = useState<{ kind: 'ok' | 'err'; text: string } | null>(null);

  function loadMe() {
    return api.get('/auth/me').then((r) => {
      setMe(r.data.user);
      setFullName(r.data.user.fullName || '');
      setEmail(r.data.user.email || '');
    });
  }

  useEffect(() => {
    loadMe().finally(() => setLoading(false));
  }, []);

  async function saveProfile(e: React.FormEvent) {
    e.preventDefault();
    setSavingProfile(true);
    setProfileMsg(null);
    try {
      const r = await api.put('/auth/me', { fullName: fullName || null, email: email || null });
      setMe((prev) => ({ ...(prev as MeUser), ...r.data.user }));
      await refreshUser();
      setProfileMsg({ kind: 'ok', text: '✓ Cambios guardados' });
    } catch (e: any) {
      setProfileMsg({ kind: 'err', text: e.response?.data?.error || 'Error al guardar' });
    } finally {
      setSavingProfile(false);
      setTimeout(() => setProfileMsg(null), 3000);
    }
  }

  async function uploadAvatar(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0];
    if (!file) return;
    setAvatarMsg(null);
    setUploadingAvatar(true);
    try {
      const fd = new FormData();
      fd.append('avatar', file);
      await api.post('/auth/me/avatar', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
      // Invalidar el cache de blobs para que se re-fetchee la nueva imagen
      if (me?.id) clearAvatarCache(me.id);
      await loadMe();
      await refreshUser();
      setAvatarMsg('✓ Avatar actualizado');
    } catch (e: any) {
      setAvatarMsg('✗ ' + (e.response?.data?.error || 'Error al subir imagen'));
    } finally {
      setUploadingAvatar(false);
      if (fileInputRef.current) fileInputRef.current.value = '';
      setTimeout(() => setAvatarMsg(null), 3000);
    }
  }

  async function deleteAvatar() {
    if (!confirm('¿Eliminar foto de perfil?')) return;
    await api.delete('/auth/me/avatar');
    if (me?.id) clearAvatarCache(me.id);
    await loadMe();
    await refreshUser();
  }

  async function changePassword(e: React.FormEvent) {
    e.preventDefault();
    setPwdMsg(null);
    if (newPwd !== confirmPwd) {
      setPwdMsg({ kind: 'err', text: 'Las contraseñas no coinciden' });
      return;
    }
    if (newPwd.length < 8) {
      setPwdMsg({ kind: 'err', text: 'La nueva contraseña debe tener al menos 8 caracteres' });
      return;
    }
    setSavingPwd(true);
    try {
      await api.post('/auth/me/change-password', { currentPassword: currentPwd, newPassword: newPwd });
      setPwdMsg({ kind: 'ok', text: '✓ Contraseña actualizada' });
      setCurrentPwd('');
      setNewPwd('');
      setConfirmPwd('');
    } catch (e: any) {
      setPwdMsg({ kind: 'err', text: e.response?.data?.error || 'Error al cambiar contraseña' });
    } finally {
      setSavingPwd(false);
    }
  }

  if (loading) return <div className="text-muted-foreground">Cargando...</div>;
  if (!me) return <div className="text-destructive">No se pudo cargar el perfil</div>;

  const fmtDate = (d: string | null) =>
    d ? new Date(d).toLocaleString('es-UY', { dateStyle: 'short', timeStyle: 'short' }) : '—';
  const displayName = me.fullName || me.username;

  return (
    <div className="space-y-6 max-w-3xl">
      <div>
        <h1 className="text-2xl font-bold tracking-tight flex items-center gap-2">
          <User className="h-6 w-6 text-primary" />
          Mi perfil
        </h1>
        <p className="text-sm text-muted-foreground mt-1">
          Tu información personal y configuración de cuenta.
        </p>
      </div>

      {/* Header con avatar + datos read-only */}
      <Card>
        <CardContent className="p-6 flex items-start gap-6">
          {/* Avatar con overlay de upload */}
          <div className="relative shrink-0 group">
            <UserAvatar
              userId={me.id}
              name={displayName}
              hasAvatar={me.hasAvatar}
              cacheBust={me.avatarUpdatedAt}
              size="xl"
            />
            <button
              type="button"
              onClick={() => fileInputRef.current?.click()}
              disabled={uploadingAvatar}
              className="absolute inset-0 rounded-full bg-black/50 text-white opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
              title="Cambiar foto"
            >
              {uploadingAvatar ? <Loader2 className="h-6 w-6 animate-spin" /> : <Camera className="h-6 w-6" />}
            </button>
            <input
              ref={fileInputRef}
              type="file"
              accept="image/jpeg,image/png,image/webp,image/gif"
              onChange={uploadAvatar}
              className="hidden"
            />
          </div>

          <div className="flex-1 space-y-2">
            <div className="flex items-center gap-2 flex-wrap">
              <h2 className="text-xl font-bold">{displayName}</h2>
              <Badge variant="secondary" className="gap-1 uppercase text-[10px]">
                <ShieldCheck className="h-3 w-3" /> {me.role}
              </Badge>
            </div>
            <div className="text-sm text-muted-foreground font-mono">@{me.username}</div>
            <div className="flex gap-2 items-center pt-1">
              <Button size="sm" variant="outline" onClick={() => fileInputRef.current?.click()} disabled={uploadingAvatar}>
                <Camera className="h-3 w-3" />
                {me.hasAvatar ? 'Cambiar foto' : 'Subir foto'}
              </Button>
              {me.hasAvatar && (
                <Button size="sm" variant="ghost" className="text-destructive" onClick={deleteAvatar}>
                  <Trash2 className="h-3 w-3" /> Eliminar
                </Button>
              )}
              {avatarMsg && <span className="text-xs font-semibold">{avatarMsg}</span>}
            </div>
            <Separator className="my-3" />
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
              <div className="flex items-center gap-2 text-muted-foreground">
                <Calendar className="h-3.5 w-3.5" />
                Miembro desde: <span className="text-foreground font-semibold">{fmtDate(me.createdAt)}</span>
              </div>
              <div className="flex items-center gap-2 text-muted-foreground">
                <Clock className="h-3.5 w-3.5" />
                Último login: <span className="text-foreground font-semibold">{fmtDate(me.lastLoginAt)}</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Edit profile */}
      <Card>
        <CardContent className="p-6">
          <h3 className="font-semibold text-base mb-1">Datos personales</h3>
          <p className="text-xs text-muted-foreground mb-4">
            Actualizá tu nombre completo y email de contacto.
          </p>
          <form onSubmit={saveProfile} className="space-y-4">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label>Usuario</Label>
                <Input value={me.username} disabled className="bg-muted/40" />
                <p className="text-[10px] text-muted-foreground">El nombre de usuario no se puede cambiar.</p>
              </div>
              <div className="space-y-1.5">
                <Label>Rol</Label>
                <Input value={me.role} disabled className="bg-muted/40 uppercase" />
              </div>
            </div>
            <div className="space-y-1.5">
              <Label>Nombre completo</Label>
              <Input value={fullName} onChange={(e) => setFullName(e.target.value)} placeholder="Juan Pérez" />
            </div>
            <div className="space-y-1.5">
              <Label>Email</Label>
              <Input type="email" value={email} onChange={(e) => setEmail(e.target.value)} placeholder="juan@horizon.com.uy" />
            </div>
            <div className="flex items-center justify-end gap-3 pt-2">
              {profileMsg && (
                <span className={`text-xs font-semibold ${profileMsg.kind === 'ok' ? 'text-green-500' : 'text-destructive'}`}>
                  {profileMsg.text}
                </span>
              )}
              <Button type="submit" disabled={savingProfile}>
                {savingProfile ? <Loader2 className="h-4 w-4 animate-spin" /> : <Save className="h-4 w-4" />}
                Guardar cambios
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      {/* Change password */}
      <Card>
        <CardContent className="p-6">
          <h3 className="font-semibold text-base mb-1 flex items-center gap-2">
            <KeyRound className="h-4 w-4 text-primary" />
            Cambiar contraseña
          </h3>
          <p className="text-xs text-muted-foreground mb-4">
            Mínimo 8 caracteres. Te vamos a pedir la contraseña actual por seguridad.
          </p>
          <form onSubmit={changePassword} className="space-y-4">
            <div className="space-y-1.5">
              <Label>Contraseña actual *</Label>
              <Input type="password" required value={currentPwd} onChange={(e) => setCurrentPwd(e.target.value)} autoComplete="current-password" />
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label>Nueva contraseña *</Label>
                <Input type="password" required value={newPwd} onChange={(e) => setNewPwd(e.target.value)} autoComplete="new-password" minLength={8} />
              </div>
              <div className="space-y-1.5">
                <Label>Confirmar nueva *</Label>
                <Input type="password" required value={confirmPwd} onChange={(e) => setConfirmPwd(e.target.value)} autoComplete="new-password" minLength={8} />
              </div>
            </div>
            <div className="flex items-center justify-end gap-3 pt-2">
              {pwdMsg && (
                <span className={`text-xs font-semibold ${pwdMsg.kind === 'ok' ? 'text-green-500' : 'text-destructive'}`}>
                  {pwdMsg.text}
                </span>
              )}
              <Button type="submit" disabled={savingPwd}>
                {savingPwd ? <Loader2 className="h-4 w-4 animate-spin" /> : <KeyRound className="h-4 w-4" />}
                Cambiar contraseña
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
