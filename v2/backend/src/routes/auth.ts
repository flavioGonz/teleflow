import { Router } from 'express';
import bcrypt from 'bcryptjs';
import multer from 'multer';
import { z } from 'zod';
import { prisma } from '../lib/prisma.js';
import { requireAuth, signToken } from '../middleware/auth.js';
import { asyncHandler } from '../lib/asyncHandler.js';

const router = Router();

const upload = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: 2 * 1024 * 1024 }, // 2MB
  fileFilter: (_req, file, cb) => {
    const ok = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(file.mimetype);
    if (ok) cb(null, true);
    else cb(new Error('Tipo de imagen no soportado (usar JPEG, PNG, WebP o GIF)') as any, false);
  },
});

const loginSchema = z.object({
  username: z.string().min(1),
  password: z.string().min(1),
});

router.post('/login', asyncHandler(async (req, res) => {
  const parsed = loginSchema.safeParse(req.body);
  if (!parsed.success) return res.status(400).json({ error: 'Datos inválidos' });

  const { username, password } = parsed.data;
  const user = await prisma.user.findUnique({ where: { username } });
  if (!user || !user.active) {
    return res.status(401).json({ error: 'Credenciales incorrectas' });
  }
  const ok = await bcrypt.compare(password, user.passwordHash);
  if (!ok) return res.status(401).json({ error: 'Credenciales incorrectas' });

  await prisma.user.update({ where: { id: user.id }, data: { lastLoginAt: new Date() } });

  const token = signToken({ userId: user.id, username: user.username, role: user.role });
  const hasAvatar = !!(await prisma.userAvatar.findUnique({ where: { userId: user.id }, select: { id: true } }));
  res.json({
    token,
    user: {
      id: user.id, username: user.username, role: user.role,
      fullName: user.fullName, email: user.email, hasAvatar,
    },
  });
}));

router.post('/logout', requireAuth, (_req, res) => {
  // Con JWT, el logout es client-side (borrar el token).
  // Acá podríamos llevar un blacklist si fuera necesario.
  res.json({ ok: true });
});

router.get('/me', requireAuth, asyncHandler(async (req, res) => {
  const user = await prisma.user.findUnique({
    where: { id: req.user!.userId },
    select: {
      id: true, username: true, role: true, fullName: true, email: true,
      lastLoginAt: true, createdAt: true,
      avatar: { select: { updatedAt: true } },
    },
  });
  if (!user) return res.status(404).json({ error: 'No encontrado' });
  // Devolvemos `hasAvatar` y un `avatarUpdatedAt` (para cache-busting en el frontend)
  res.json({
    user: {
      id: user.id, username: user.username, role: user.role,
      fullName: user.fullName, email: user.email,
      lastLoginAt: user.lastLoginAt, createdAt: user.createdAt,
      hasAvatar: !!user.avatar,
      avatarUpdatedAt: user.avatar?.updatedAt ?? null,
    },
  });
}));

/** GET /api/auth/avatar/:userId — sirve los bytes del avatar (PROTEGIDO con JWT) */
router.get('/avatar/:userId', requireAuth, asyncHandler(async (req, res) => {
  const userId = parseInt(req.params.userId, 10);
  if (!userId) return res.status(404).end();
  const av = await prisma.userAvatar.findUnique({ where: { userId } });
  if (!av) return res.status(404).end();
  res.setHeader('Content-Type', av.mimeType);
  // Cache privado por user — el browser no lo comparte entre usuarios
  res.setHeader('Cache-Control', 'private, max-age=300');
  res.send(av.data);
}));

/** POST /api/auth/me/avatar — subir avatar (multipart, field: "avatar") */
router.post('/me/avatar', requireAuth, upload.single('avatar'), asyncHandler(async (req, res) => {
  if (!req.file) return res.status(400).json({ error: 'Archivo requerido (field "avatar")' });
  await prisma.userAvatar.upsert({
    where: { userId: req.user!.userId },
    update: { mimeType: req.file.mimetype, data: req.file.buffer },
    create: { userId: req.user!.userId, mimeType: req.file.mimetype, data: req.file.buffer },
  });
  res.json({ ok: true });
}));

/** DELETE /api/auth/me/avatar — borrar avatar */
router.delete('/me/avatar', requireAuth, asyncHandler(async (req, res) => {
  await prisma.userAvatar.delete({ where: { userId: req.user!.userId } }).catch(() => {});
  res.json({ ok: true });
}));

const updateMeSchema = z.object({
  fullName: z.string().min(1).max(100).optional(),
  email: z.string().email().nullish(),
});

router.put('/me', requireAuth, asyncHandler(async (req, res) => {
  const parsed = updateMeSchema.safeParse(req.body);
  if (!parsed.success) return res.status(400).json({ error: 'Datos inválidos' });
  const user = await prisma.user.update({
    where: { id: req.user!.userId },
    data: parsed.data,
    select: { id: true, username: true, role: true, fullName: true, email: true, lastLoginAt: true, createdAt: true },
  });
  res.json({ user });
}));

const changePasswordSchema = z.object({
  currentPassword: z.string().min(1),
  newPassword: z.string().min(8, 'La nueva contraseña debe tener al menos 8 caracteres'),
});

router.post('/me/change-password', requireAuth, asyncHandler(async (req, res) => {
  const parsed = changePasswordSchema.safeParse(req.body);
  if (!parsed.success) {
    return res.status(400).json({ error: parsed.error.issues[0]?.message || 'Datos inválidos' });
  }
  const { currentPassword, newPassword } = parsed.data;

  const user = await prisma.user.findUnique({ where: { id: req.user!.userId } });
  if (!user) return res.status(404).json({ error: 'Usuario no encontrado' });

  const ok = await bcrypt.compare(currentPassword, user.passwordHash);
  if (!ok) return res.status(400).json({ error: 'Contraseña actual incorrecta' });

  const newHash = await bcrypt.hash(newPassword, 10);
  await prisma.user.update({ where: { id: user.id }, data: { passwordHash: newHash } });
  res.json({ ok: true });
}));

export default router;
