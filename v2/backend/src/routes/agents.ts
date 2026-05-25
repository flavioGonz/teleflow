import { Router } from 'express';
import bcrypt from 'bcryptjs';
import { z } from 'zod';
import { prisma } from '../lib/prisma.js';
import { requireAuth } from '../middleware/auth.js';
import { asyncHandler } from '../lib/asyncHandler.js';

const router = Router();
router.use(requireAuth);

const DAY_VALUES = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'] as const;

/** Estructura de un turno laboral */
const shiftSchema = z.object({
  start: z.string().regex(/^([01]\d|2[0-3]):[0-5]\d$/, 'Formato hora inválido (HH:MM)'),
  end:   z.string().regex(/^([01]\d|2[0-3]):[0-5]\d$/, 'Formato hora inválido (HH:MM)'),
  days:  z.array(z.enum(DAY_VALUES)).min(1, 'Seleccionar al menos un día'),
});

/** Schema para CREATE — sin `number`, lo genera el backend */
const createAgentSchema = z.object({
  name: z.string().min(1),
  email: z.string().email().nullish().or(z.literal('').transform(() => null)),
  phone: z.string().nullish(),
  password: z.string().nullish(),
  active: z.boolean().default(true),
  sector: z.string().nullish(),
  shift: shiftSchema.nullish(),
  notes: z.string().nullish(),
});

/** Schema para UPDATE — number nunca se edita */
const updateAgentSchema = createAgentSchema.partial();

/**
 * GET /api/agents/next-number — devuelve el próximo número disponible.
 * Busca el máximo numérico existente y suma 1. Si no hay agentes, arranca en 200
 * (convención del proyecto — agentes "humanos" empiezan en 200, infra en 100x).
 */
router.get('/next-number', asyncHandler(async (_req, res) => {
  const agents = await prisma.agent.findMany({ select: { number: true } });
  // Solo considera números enteros (ignora "Ext 200" o similar)
  const numbers = agents
    .map((a) => parseInt(a.number, 10))
    .filter((n) => !Number.isNaN(n) && n > 0);
  const next = numbers.length ? Math.max(...numbers) + 1 : 200;
  res.json({ next: String(next) });
}));

router.get('/', asyncHandler(async (_req, res) => {
  const agents = await prisma.agent.findMany({
    orderBy: { number: 'asc' },
    include: {
      sessions: { where: { logoutTime: null }, take: 1 },
      pauses: { where: { endTime: null }, take: 1, include: { pauseType: true } },
    },
  });
  const enriched = agents.map(a => ({
    ...a,
    logged_in: a.sessions.length > 0,
    current_extension: a.sessions[0]?.extension ?? null,
    paused: a.pauses.length > 0,
    pause_reason: a.pauses[0]?.pauseType?.label ?? a.pauses[0]?.reason ?? null,
  }));
  res.json({ agents: enriched });
}));

router.get('/:id', asyncHandler(async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const agent = await prisma.agent.findUnique({
    where: { id },
    include: { sessions: { orderBy: { loginTime: 'desc' }, take: 20 }, pauses: { orderBy: { startTime: 'desc' }, take: 20 }, queuePrefs: true },
  });
  if (!agent) return res.status(404).json({ error: 'Agente no encontrado' });
  res.json({ agent });
}));

router.post('/', asyncHandler(async (req, res) => {
  const parsed = createAgentSchema.safeParse(req.body);
  if (!parsed.success) return res.status(400).json({ error: parsed.error.format() });
  const data = parsed.data;
  const passwordHash = data.password ? await bcrypt.hash(data.password, 10) : null;

  // Generar próximo number automáticamente (mismo algoritmo que /next-number)
  const all = await prisma.agent.findMany({ select: { number: true } });
  const numbers = all.map((a) => parseInt(a.number, 10)).filter((n) => !Number.isNaN(n) && n > 0);
  const nextNumber = String(numbers.length ? Math.max(...numbers) + 1 : 200);

  const agent = await prisma.agent.create({
    data: { ...data, password: passwordHash, number: nextNumber },
  });
  res.json({ agent });
}));

router.put('/:id', asyncHandler(async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const parsed = updateAgentSchema.safeParse(req.body);
  if (!parsed.success) return res.status(400).json({ error: parsed.error.format() });
  const data: any = { ...parsed.data };
  if (data.password) data.password = await bcrypt.hash(data.password, 10);
  // `number` nunca se actualiza, aunque venga en el body — se ignora.
  delete data.number;
  const agent = await prisma.agent.update({ where: { id }, data });
  res.json({ agent });
}));

router.delete('/:id', asyncHandler(async (req, res) => {
  const id = parseInt(req.params.id, 10);
  await prisma.agent.delete({ where: { id } });
  res.json({ ok: true });
}));

/** Métricas de un agente — total tiempo logueado + llamadas + pausas */
router.get('/:id/metrics', asyncHandler(async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const startStr = (req.query.start as string) || new Date(Date.now() - 7 * 86400000).toISOString();
  const endStr = (req.query.end as string) || new Date().toISOString();
  const start = new Date(startStr);
  const end = new Date(endStr);

  const sessions = await prisma.agentSession.findMany({
    where: { agentId: id, loginTime: { gte: start, lte: end } },
    orderBy: { loginTime: 'desc' },
  });
  const pauses = await prisma.agentPause.findMany({
    where: { agentId: id, startTime: { gte: start, lte: end } },
    include: { pauseType: true },
    orderBy: { startTime: 'desc' },
  });

  const totalLoginSec = sessions.reduce((acc, s) => {
    const end = s.logoutTime ?? new Date();
    return acc + Math.floor((+end - +s.loginTime) / 1000);
  }, 0);
  const totalPausedSec = pauses.reduce((acc, p) => {
    const end = p.endTime ?? new Date();
    return acc + Math.floor((+end - +p.startTime) / 1000);
  }, 0);

  res.json({
    sessions,
    pauses,
    totals: { totalLoginSec, totalPausedSec, sessionsCount: sessions.length, pausesCount: pauses.length },
  });
}));

export default router;
