/**
 * Reportes y KPIs — agregaciones sobre CDR + agent_sessions.
 */
import { Router } from 'express';
import { cdrq } from '../services/asterisk-db.js';
import { prisma } from '../lib/prisma.js';
import { requireAuth } from '../middleware/auth.js';
import { asyncHandler } from '../lib/asyncHandler.js';

const router = Router();
router.use(requireAuth);

/** GET /api/reports/summary?start=...&end=... */
router.get('/summary', asyncHandler(async (req, res) => {
  const start = (req.query.start as string) || new Date().toISOString().slice(0, 10);
  const end = (req.query.end as string) || start;

  const rows = await cdrq(
    `SELECT
       COUNT(*) AS total,
       SUM(disposition = 'ANSWERED') AS answered,
       SUM(disposition = 'NO ANSWER') AS no_answer,
       SUM(disposition = 'BUSY') AS busy,
       SUM(disposition = 'FAILED') AS failed,
       AVG(billsec) AS avg_talk,
       AVG(duration - billsec) AS avg_wait
     FROM cdr
     WHERE calldate BETWEEN ? AND ?`,
    [start, `${end} 23:59:59`]
  );
  // mysql2 devuelve SUM/AVG como strings — castear a number para que recharts funcione.
  const r: any = rows[0] || {};
  const stats = {
    total: Number(r.total) || 0,
    answered: Number(r.answered) || 0,
    no_answer: Number(r.no_answer) || 0,
    busy: Number(r.busy) || 0,
    failed: Number(r.failed) || 0,
    avg_talk: Number(r.avg_talk) || 0,
    avg_wait: Number(r.avg_wait) || 0,
  };
  res.json({ stats });
}));

/** GET /api/reports/by-queue?start=...&end=... */
router.get('/by-queue', asyncHandler(async (req, res) => {
  const start = (req.query.start as string) || new Date(Date.now() - 7 * 86400000).toISOString().slice(0, 10);
  const end = (req.query.end as string) || new Date().toISOString().slice(0, 10);

  const rows = await prisma.queueCallEntry.groupBy({
    by: ['queueId'],
    where: { enteredAt: { gte: new Date(start), lte: new Date(`${end}T23:59:59`) } },
    _count: { id: true },
    _avg: { waitTime: true, talkTime: true },
    _sum: { abandoned: false ? 0 : undefined } as any,
  });
  res.json({ queues: rows });
}));

/** GET /api/reports/by-agent?start=...&end=... */
router.get('/by-agent', asyncHandler(async (req, res) => {
  const start = (req.query.start as string) || new Date(Date.now() - 7 * 86400000).toISOString().slice(0, 10);
  const end = (req.query.end as string) || new Date().toISOString().slice(0, 10);

  const agents = await prisma.agent.findMany({
    where: { active: true },
    include: {
      sessions: { where: { loginTime: { gte: new Date(start), lte: new Date(`${end}T23:59:59`) } } },
      pauses: { where: { startTime: { gte: new Date(start), lte: new Date(`${end}T23:59:59`) } } },
    },
  });
  const out = agents.map(a => {
    const totalLogin = a.sessions.reduce((acc, s) => acc + Math.floor(((s.logoutTime ?? new Date()).getTime() - s.loginTime.getTime()) / 1000), 0);
    const totalPaused = a.pauses.reduce((acc, p) => acc + Math.floor(((p.endTime ?? new Date()).getTime() - p.startTime.getTime()) / 1000), 0);
    return {
      id: a.id,
      number: a.number,
      name: a.name,
      sessions: a.sessions.length,
      total_login_sec: totalLogin,
      total_paused_sec: totalPaused,
    };
  });
  res.json({ agents: out });
}));

export default router;
