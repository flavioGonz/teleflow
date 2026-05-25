/**
 * Stream de grabaciones — el archivo vive en el filesystem del PBX.
 * Para dev local se puede apuntar a un mock o requerir SSH/mount.
 */
import { Router } from 'express';
import fs from 'fs';
import path from 'path';
import { requireAuth } from '../middleware/auth.js';
import { asyncHandler } from '../lib/asyncHandler.js';

const router = Router();
router.use(requireAuth);

const RECORDINGS_DIR = process.env.RECORDINGS_PATH || '/var/cache/teleflow/recordings';

/** GET /api/recordings/file?path=... — stream wav/mp3 */
router.get('/file', asyncHandler(async (req, res) => {
  const rel = (req.query.path as string) || '';
  // Sanitize — prevent path traversal
  if (rel.includes('..') || rel.includes('\0')) return res.status(400).json({ error: 'path inválido' });
  const full = path.join(RECORDINGS_DIR, rel);
  if (!fs.existsSync(full)) return res.status(404).json({ error: 'No encontrado' });

  const ext = path.extname(full).toLowerCase();
  const mime = ext === '.mp3' ? 'audio/mpeg' : ext === '.wav' ? 'audio/wav' : 'application/octet-stream';
  res.setHeader('Content-Type', mime);
  fs.createReadStream(full).pipe(res);
}));

export default router;
