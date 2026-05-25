import { useEffect, useState } from 'react';
import { cn } from '../lib/utils';
import { api } from '../lib/api';

/** Extrae 2 letras del nombre: "Juan Pérez" → "JP", "admin" → "AD" */
export function getInitials(name?: string | null): string {
  const n = (name || '').trim();
  if (!n) return '?';
  const parts = n.split(/\s+/).filter(Boolean);
  if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  return n.slice(0, 2).toUpperCase();
}

interface UserAvatarProps {
  userId?: number | null;
  name?: string | null;
  hasAvatar?: boolean;
  /** Para forzar re-fetch cuando se actualiza el avatar (ej. timestamp del updatedAt) */
  cacheBust?: string | number | null;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  className?: string;
  onClick?: () => void;
}

const sizeClasses: Record<NonNullable<UserAvatarProps['size']>, string> = {
  sm: 'h-7 w-7 text-[10px]',
  md: 'h-9 w-9 text-sm',
  lg: 'h-14 w-14 text-lg',
  xl: 'h-24 w-24 text-3xl',
};

/**
 * Cache en memoria de blob URLs por (userId|cacheBust) — para no re-fetchear la misma imagen
 * cuando el componente se renderea en múltiples lugares del UI (topbar + perfil + etc).
 */
const blobCache = new Map<string, string>();

async function fetchAvatarBlob(userId: number, cacheBust?: string | number | null): Promise<string | null> {
  const key = `${userId}|${cacheBust ?? ''}`;
  const cached = blobCache.get(key);
  if (cached) return cached;
  try {
    const r = await api.get(`/auth/avatar/${userId}`, { responseType: 'blob' });
    const url = URL.createObjectURL(r.data as Blob);
    blobCache.set(key, url);
    return url;
  } catch {
    return null;
  }
}

/** Limpiar el cache del avatar de un usuario (útil al subir/borrar foto) */
export function clearAvatarCache(userId?: number) {
  if (userId == null) {
    for (const url of blobCache.values()) URL.revokeObjectURL(url);
    blobCache.clear();
    return;
  }
  for (const [key, url] of blobCache.entries()) {
    if (key.startsWith(`${userId}|`)) {
      URL.revokeObjectURL(url);
      blobCache.delete(key);
    }
  }
}

export function UserAvatar({
  userId, name, hasAvatar, cacheBust, size = 'md', className, onClick,
}: UserAvatarProps) {
  const initials = getInitials(name);
  const [blobUrl, setBlobUrl] = useState<string | null>(null);

  useEffect(() => {
    if (!hasAvatar || !userId) { setBlobUrl(null); return; }
    let cancelled = false;
    fetchAvatarBlob(userId, cacheBust).then((url) => {
      if (!cancelled) setBlobUrl(url);
    });
    return () => { cancelled = true; };
  }, [userId, hasAvatar, cacheBust]);

  return (
    <div
      onClick={onClick}
      className={cn(
        'rounded-full bg-primary text-primary-foreground font-black flex items-center justify-center overflow-hidden shrink-0 select-none',
        onClick && 'cursor-pointer',
        sizeClasses[size],
        className
      )}
      title={name || undefined}
    >
      {blobUrl ? (
        <img src={blobUrl} alt={name || 'avatar'} className="h-full w-full object-cover" />
      ) : (
        <span>{initials}</span>
      )}
    </div>
  );
}
