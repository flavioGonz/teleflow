import { Router } from 'express';
import { cdrq } from '../services/asterisk-db.js';
import { requireAuth } from '../middleware/auth.js';
import { asyncHandler } from '../lib/asyncHandler.js';

const router = Router();
router.use(requireAuth);

/** GET /api/cdr?start=...&end=...&src=...&dst=...&disposition=...&limit=... */
router.get('/', asyncHandler(async (req, res) => {
  const start = (req.query.start as string) || new Date(Date.now() - 7 * 86400000).toISOString().slice(0, 10);
  const end = (req.query.end as string) || new Date().toISOString().slice(0, 10);
  const src = req.query.src as string | undefined;
  const dst = req.query.dst as string | undefined;
  const disposition = req.query.disposition as string | undefined;
  const limit = Math.min(parseInt((req.query.limit as string) || '500', 10), 2000);

  const where: string[] = ['calldate BETWEEN ? AND ?'];
  const params: any[] = [start, `${end} 23:59:59`];
  if (src) { where.push('src LIKE ?'); params.push(`%${src}%`); }
  if (dst) { where.push('dst LIKE ?'); params.push(`%${dst}%`); }
  if (disposition) { where.push('disposition = ?'); params.push(disposition); }

  const rows = await cdrq(
    `SELECT calldate, src, dst, dcontext, duration, billsec, disposition, recordingfile, uniqueid
     FROM cdr WHERE ${where.join(' AND ')}
     ORDER BY calldate DESC LIMIT ${limit}`,
    params
  );
  res.json({ cdr: rows, count: rows.length });
}));

export default router;
