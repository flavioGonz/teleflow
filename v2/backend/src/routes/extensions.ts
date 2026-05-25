/**
 * Extensiones SIP — la data vive en MySQL Asterisk (devices/users tables).
 * Acá hacemos JOIN con la metadata propia (ExtMeta) de Postgres.
 */
import { Router } from 'express';
import { aq, asteriskDb } from '../services/asterisk-db.js';
import { prisma } from '../lib/prisma.js';
import { requireAuth } from '../middleware/auth.js';
import { asyncHandler } from '../lib/asyncHandler.js';

const router = Router();
router.use(requireAuth);

/** GET /api/extensions — lista completa con status + metadata */
router.get('/', asyncHandler(async (_req, res) => {
  const devices = await aq(`
    SELECT d.id AS ext, d.tech, d.description AS name, u.voicemail, u.recording,
           s.data AS secret_data
    FROM devices d
    LEFT JOIN users u ON u.extension = d.id
    LEFT JOIN sip s ON s.id = d.id AND s.keyword = 'secret'
    WHERE d.devicetype = 'fixed'
    ORDER BY CAST(d.id AS UNSIGNED)
  `);
  const meta = await prisma.extMeta.findMany();
  const metaMap = new Map(meta.map(m => [m.ext, m]));
  const merged = devices.map(d => ({
    ...d,
    meta: metaMap.get(d.ext) || null,
  }));
  res.json({ extensions: merged });
}));

/** GET /api/extensions/:ext */
router.get('/:ext', asyncHandler(async (req, res) => {
  const ext = req.params.ext;
  const rows = await aq('SELECT * FROM users WHERE extension = ?', [ext]);
  if (!rows.length) return res.status(404).json({ error: 'Extensión no encontrada' });
  const sipRows = await aq('SELECT keyword, data FROM sip WHERE id = ?', [ext]);
  const sip: Record<string, string> = {};
  for (const r of sipRows) sip[r.keyword] = r.data;
  const meta = await prisma.extMeta.findUnique({ where: { ext } });
  res.json({ user: rows[0], sip, meta });
}));

/** POST /api/extensions — crear */
router.post('/', asyncHandler(async (req, res) => {
  const { ext, name, secret, voicemail = 'novm' } = req.body;
  if (!ext || !name) return res.status(400).json({ error: 'ext y name requeridos' });

  const conn = await asteriskDb.getConnection();
  try {
    await conn.beginTransaction();
    await conn.query(
      `INSERT INTO devices (id, tech, dial, devicetype, user, description, emergency_cid)
       VALUES (?, 'pjsip', ?, 'fixed', ?, ?, '')`,
      [ext, `PJSIP/${ext}`, ext, name]
    );
    await conn.query(
      `INSERT INTO users (extension, password, name, voicemail, ringtimer, noanswer, recording, outboundcid, mohclass)
       VALUES (?, ?, ?, ?, 0, '', 'out=Always|in=Always', '', 'default')`,
      [ext, secret || '', name, voicemail]
    );
    // SIP keywords mínimos
    const sipPairs: [string, string][] = [
      ['secret', secret || ''],
      ['type', 'friend'],
      ['host', 'dynamic'],
      ['context', 'from-internal'],
      ['callerid', `${name} <${ext}>`],
    ];
    for (const [k, v] of sipPairs) {
      await conn.query('INSERT INTO sip (id, keyword, data, flags) VALUES (?, ?, ?, 0)', [ext, k, v]);
    }
    await conn.commit();
    res.json({ ok: true, ext });
  } catch (e: any) {
    await conn.rollback();
    res.status(500).json({ error: e.message });
  } finally {
    conn.release();
  }
}));

/** PUT /api/extensions/:ext — actualizar */
router.put('/:ext', asyncHandler(async (req, res) => {
  const ext = req.params.ext;
  const { name, secret, recording } = req.body;
  if (name) await aq('UPDATE users SET name = ? WHERE extension = ?', [name, ext]);
  if (secret) {
    await aq('UPDATE sip SET data = ? WHERE id = ? AND keyword = ?', [secret, ext, 'secret']);
    await aq('UPDATE users SET password = ? WHERE extension = ?', [secret, ext]);
  }
  if (recording) await aq('UPDATE users SET recording = ? WHERE extension = ?', [recording, ext]);
  res.json({ ok: true });
}));

/** DELETE /api/extensions/:ext */
router.delete('/:ext', asyncHandler(async (req, res) => {
  const ext = req.params.ext;
  await aq('DELETE FROM devices WHERE id = ?', [ext]);
  await aq('DELETE FROM users WHERE extension = ?', [ext]);
  await aq('DELETE FROM sip WHERE id = ?', [ext]);
  await prisma.extMeta.delete({ where: { ext } }).catch(() => {});
  res.json({ ok: true });
}));

export default router;
