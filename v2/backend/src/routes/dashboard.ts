/**
 * Dashboard — agregado de todo lo que aparece en la home.
 * Reemplaza el monolito `?action=get_full_data` del PHP.
 */
import { Router } from 'express';
import { aq } from '../services/asterisk-db.js';
import { prisma } from '../lib/prisma.js';
import { requireAuth } from '../middleware/auth.js';
import { asyncHandler } from '../lib/asyncHandler.js';

const router = Router();
router.use(requireAuth);

router.get('/', asyncHandler(async (_req, res) => {
  // Counts de extensiones, colas, agentes online
  const [extCount, queueCount, agentSessions] = await Promise.all([
    aq('SELECT COUNT(*) AS c FROM devices WHERE devicetype = "fixed"').then(r => r[0]?.c ?? 0).catch(() => 0),
    aq('SELECT COUNT(*) AS c FROM queues_config').then(r => r[0]?.c ?? 0).catch(() => 0),
    prisma.agentSession.count({ where: { logoutTime: null } }),
  ]);

  const agentsInPause = await prisma.agentPause.count({ where: { endTime: null } });

  res.json({
    counts: {
      extensions: Number(extCount),
      queues: Number(queueCount),
      agentsLogged: agentSessions,
      agentsInPause,
    },
  });
}));

export default router;
