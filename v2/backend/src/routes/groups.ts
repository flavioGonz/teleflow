import { Router } from 'express';
import { aq } from '../services/asterisk-db.js';
import { requireAuth } from '../middleware/auth.js';
import { asyncHandler } from '../lib/asyncHandler.js';

const router = Router();
router.use(requireAuth);

router.get('/', asyncHandler(async (_req, res) => {
  const groups = await aq(`SELECT grpnum, description, strategy, grptime, grplist FROM ringgroups ORDER BY grpnum`);
  res.json({ groups });
}));

router.post('/', asyncHandler(async (req, res) => {
  const { grpnum, description, strategy = 'ringall', grptime = 20, grplist = '' } = req.body;
  await aq(
    `INSERT INTO ringgroups (grpnum, description, strategy, grptime, grplist) VALUES (?, ?, ?, ?, ?)`,
    [grpnum, description, strategy, grptime, grplist]
  );
  res.json({ ok: true });
}));

router.put('/:grpnum', asyncHandler(async (req, res) => {
  const { description, strategy, grptime, grplist } = req.body;
  await aq(
    `UPDATE ringgroups SET description = ?, strategy = ?, grptime = ?, grplist = ? WHERE grpnum = ?`,
    [description, strategy, grptime, grplist, req.params.grpnum]
  );
  res.json({ ok: true });
}));

router.delete('/:grpnum', asyncHandler(async (req, res) => {
  await aq('DELETE FROM ringgroups WHERE grpnum = ?', [req.params.grpnum]);
  res.json({ ok: true });
}));

export default router;
