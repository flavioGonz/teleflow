import { Router } from 'express';
import { prisma } from '../lib/prisma.js';
import { requireAuth } from '../middleware/auth.js';
import { asyncHandler } from '../lib/asyncHandler.js';

const router = Router();
router.use(requireAuth);

router.get('/', asyncHandler(async (_req, res) => {
  const rows = await prisma.extMeta.findMany();
  const map: Record<string, any> = {};
  for (const r of rows) map[r.ext] = r;
  res.json({ meta: map });
}));

router.get('/:ext', asyncHandler(async (req, res) => {
  const meta = await prisma.extMeta.findUnique({ where: { ext: req.params.ext } });
  res.json({ meta });
}));

router.put('/:ext', asyncHandler(async (req, res) => {
  const { ext } = req.params;
  const { avatarUrl, tipo, rtspUrl, rtspLabel, location, notes, customFields } = req.body;
  const data = { avatarUrl, tipo, rtspUrl, rtspLabel, location, notes, customFields };
  const meta = await prisma.extMeta.upsert({
    where: { ext },
    update: data,
    create: { ext, ...data },
  });
  res.json({ meta });
}));

router.delete('/:ext', asyncHandler(async (req, res) => {
  await prisma.extMeta.delete({ where: { ext: req.params.ext } }).catch(() => {});
  res.json({ ok: true });
}));

export default router;
