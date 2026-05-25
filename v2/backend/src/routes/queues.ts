/**
 * Colas — la config "fija" está en queues_config (extension + descr + opciones).
 * Los atributos dinámicos (strategy, timeout, members, etc.) están en queues_details
 * como key-value (id+keyword+data).
 */
import { Router } from 'express';
import { aq } from '../services/asterisk-db.js';
import { requireAuth } from '../middleware/auth.js';
import { asyncHandler } from '../lib/asyncHandler.js';

const router = Router();
router.use(requireAuth);

interface QueueRow {
  extension: string;
  descr: string;
  strategy?: string;
  timeout?: number;
  retry?: number;
  weight?: number;
  maxlen?: number;
  members: { interface: string }[];
}

router.get('/', asyncHandler(async (_req, res) => {
  const configs = await aq<{ extension: string; descr: string }>(
    `SELECT extension, descr FROM queues_config ORDER BY extension`
  );
  const details = await aq<{ id: string; keyword: string; data: string }>(
    `SELECT id, keyword, data FROM queues_details`
  );

  // Index details por queue id
  const byQueue = new Map<string, Record<string, any>>();
  const membersByQueue = new Map<string, { interface: string }[]>();
  for (const d of details) {
    if (!byQueue.has(d.id)) byQueue.set(d.id, {});
    if (d.keyword === 'member') {
      if (!membersByQueue.has(d.id)) membersByQueue.set(d.id, []);
      membersByQueue.get(d.id)!.push({ interface: d.data });
    } else {
      byQueue.get(d.id)![d.keyword] = d.data;
    }
  }

  const queues: QueueRow[] = configs.map((c) => {
    const opts = byQueue.get(c.extension) || {};
    return {
      id: c.extension,           // alias para frontend (espera "id")
      extension: c.extension,
      name: c.descr,             // alias para frontend (espera "name")
      descr: c.descr,
      strategy: opts.strategy || 'ringall',
      timeout: parseInt(opts.timeout) || 15,
      retry: parseInt(opts.retry) || 5,
      weight: parseInt(opts.weight) || 0,
      maxlen: parseInt(opts.maxlen) || 0,
      members: membersByQueue.get(c.extension) || [],
    } as any;
  });

  res.json({ queues });
}));

router.post('/', asyncHandler(async (req, res) => {
  const { id, name, strategy = 'ringall', timeout = 15, retry = 5, weight = 0, maxlen = 0 } = req.body;
  if (!id || !name) return res.status(400).json({ error: 'id y name requeridos' });
  await aq(
    `INSERT INTO queues_config (extension, descr) VALUES (?, ?)`,
    [id, name]
  );
  // Set defaults en queues_details
  const opts: [string, string][] = [
    ['strategy', strategy], ['timeout', String(timeout)],
    ['retry', String(retry)], ['weight', String(weight)], ['maxlen', String(maxlen)],
  ];
  for (const [k, v] of opts) {
    await aq('INSERT INTO queues_details (id, keyword, data, flags) VALUES (?, ?, ?, 0)', [id, k, v]);
  }
  res.json({ ok: true, id });
}));

router.put('/:id', asyncHandler(async (req, res) => {
  const { name, strategy, timeout, retry, weight, maxlen } = req.body;
  await aq(`UPDATE queues_config SET descr = ? WHERE extension = ?`, [name, req.params.id]);
  // Upsert key-values en queues_details
  const upserts: [string, any][] = [
    ['strategy', strategy], ['timeout', timeout], ['retry', retry],
    ['weight', weight], ['maxlen', maxlen],
  ];
  for (const [k, v] of upserts) {
    if (v === undefined || v === null) continue;
    await aq(
      `INSERT INTO queues_details (id, keyword, data, flags) VALUES (?, ?, ?, 0)
       ON DUPLICATE KEY UPDATE data = VALUES(data)`,
      [req.params.id, k, String(v)]
    );
  }
  res.json({ ok: true });
}));

router.delete('/:id', asyncHandler(async (req, res) => {
  await aq('DELETE FROM queues_config WHERE extension = ?', [req.params.id]);
  await aq('DELETE FROM queues_details WHERE id = ?', [req.params.id]);
  res.json({ ok: true });
}));

export default router;
