import { Router } from 'express';
import fs from 'fs';
import path from 'path';
import { requireAuth } from '../middleware/auth.js';
import { asyncHandler } from '../lib/asyncHandler.js';
import { aq } from '../services/asterisk-db.js';

const router = Router();
router.use(requireAuth);

const IVR_JSON_PATH = process.env.IVR_JSON_PATH || path.join(process.cwd(), 'data', 'ivr_flow.json');

router.get('/', asyncHandler(async (_req, res) => {
  // Flow visual (JSON local) + detalle de IVR de FreePBX
  let flow: any = null;
  try { flow = JSON.parse(fs.readFileSync(IVR_JSON_PATH, 'utf-8')); } catch { /* nada */ }
  const details = await aq('SELECT id, displayname FROM ivr_details').catch(() => []);
  res.json({ flow, details });
}));

router.put('/flow', asyncHandler(async (req, res) => {
  fs.mkdirSync(path.dirname(IVR_JSON_PATH), { recursive: true });
  fs.writeFileSync(IVR_JSON_PATH, JSON.stringify(req.body, null, 2));
  res.json({ ok: true });
}));

export default router;
