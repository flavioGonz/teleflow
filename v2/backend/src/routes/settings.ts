import { Router } from 'express';
import { prisma } from '../lib/prisma.js';
import { requireAuth } from '../middleware/auth.js';
import { asyncHandler } from '../lib/asyncHandler.js';

const router = Router();
router.use(requireAuth);

router.get('/', asyncHandler(async (_req, res) => {
  const rows = await prisma.appSetting.findMany();
  const map: Record<string, string> = {};
  for (const r of rows) map[r.key] = r.value;
  res.json({ settings: map });
}));

router.put('/', asyncHandler(async (req, res) => {
  const settings = req.body as Record<string, string>;
  const updates = Object.entries(settings).map(([key, value]) =>
    prisma.appSetting.upsert({
      where: { key },
      update: { value: String(value ?? '') },
      create: { key, value: String(value ?? '') },
    })
  );
  await Promise.all(updates);
  res.json({ ok: true, count: updates.length });
}));

export default router;
