/**
 * Hotdesking — el agente se loguea en cualquier extensión física,
 * el sistema lo agrega como member dinámico de las queues correspondientes.
 */
import { Router } from 'express';
import { z } from 'zod';
import { prisma } from '../lib/prisma.js';
import { requireAuth } from '../middleware/auth.js';
import { asyncHandler } from '../lib/asyncHandler.js';
import { amiAction } from '../services/asterisk-ami.js';

const router = Router();
router.use(requireAuth);

/** GET /api/hotdesking — agentes con sesiones activas */
router.get('/', asyncHandler(async (_req, res) => {
  const sessions = await prisma.agentSession.findMany({
    where: { logoutTime: null },
    include: { agent: true },
    orderBy: { loginTime: 'desc' },
  });
  const agents = sessions.map(s => ({
    session_id: s.id,
    agent_id: s.agent.id,
    agent_number: s.agent.number,
    name: s.agent.name,
    extension: s.extension,
    login_time: s.loginTime,
    queues: s.queues?.split(',').filter(Boolean) ?? [],
    logged_in: true,
  }));
  res.json({ status: 'ok', agents });
}));

const loginSchema = z.object({
  agent_number: z.string().min(1),
  extension: z.string().min(1),
  queues: z.array(z.string()).optional(),
  password: z.string().optional(),
});

/** POST /api/hotdesking/login */
router.post('/login', asyncHandler(async (req, res) => {
  const parsed = loginSchema.safeParse(req.body);
  if (!parsed.success) return res.status(400).json({ status: 'error', message: 'Datos inválidos' });
  const { agent_number, extension, queues = [], password } = parsed.data;

  const agent = await prisma.agent.findUnique({ where: { number: agent_number } });
  if (!agent || !agent.active) {
    return res.status(404).json({ status: 'error', message: 'Agente no encontrado' });
  }
  // Opcional: validar password si el endpoint lo manda
  if (password && agent.password) {
    const bcrypt = await import('bcryptjs');
    const ok = await bcrypt.compare(password, agent.password);
    if (!ok) return res.status(401).json({ status: 'error', message: 'Password incorrecto' });
  }

  // Cerrar sesiones previas del mismo agente o de la misma extensión
  await prisma.agentSession.updateMany({
    where: { OR: [{ agentId: agent.id }, { extension }], logoutTime: null },
    data: { logoutTime: new Date() },
  });

  const session = await prisma.agentSession.create({
    data: {
      agentId: agent.id,
      extension,
      queues: queues.join(','),
    },
  });

  // AMI — agregar a queues
  for (const q of queues) {
    try {
      await amiAction({
        Action: 'QueueAdd',
        Queue: q,
        Interface: `PJSIP/${extension}`,
        MemberName: `${agent.name} (${agent.number})`,
        StateInterface: `PJSIP/${extension}`,
      });
    } catch (e: any) {
      console.warn(`[hotdesking] QueueAdd failed for ${q}:`, e.message);
    }
  }

  res.json({ status: 'ok', session_id: session.id, agent_id: agent.id });
}));

const logoutSchema = z.object({
  agent_number: z.string().optional(),
  extension: z.string().optional(),
});

/** POST /api/hotdesking/logout */
router.post('/logout', asyncHandler(async (req, res) => {
  const parsed = logoutSchema.safeParse(req.body);
  if (!parsed.success) return res.status(400).json({ status: 'error', message: 'Datos inválidos' });
  const { agent_number, extension } = parsed.data;
  if (!agent_number && !extension) return res.status(400).json({ status: 'error', message: 'agent_number o extension requeridos' });

  const where: any = { logoutTime: null };
  if (agent_number) {
    const agent = await prisma.agent.findUnique({ where: { number: agent_number } });
    if (agent) where.agentId = agent.id;
  }
  if (extension) where.extension = extension;

  const sessions = await prisma.agentSession.findMany({ where });
  if (!sessions.length) return res.json({ status: 'ok', closed: 0 });

  // Quitar de queues vía AMI
  for (const s of sessions) {
    const qs = s.queues?.split(',').filter(Boolean) ?? [];
    for (const q of qs) {
      try {
        await amiAction({ Action: 'QueueRemove', Queue: q, Interface: `PJSIP/${s.extension}` });
      } catch (e: any) {
        console.warn(`[hotdesking] QueueRemove failed for ${q}:`, e.message);
      }
    }
  }

  await prisma.agentSession.updateMany({ where, data: { logoutTime: new Date() } });
  res.json({ status: 'ok', closed: sessions.length });
}));

/** POST /api/hotdesking/pause */
router.post('/pause', asyncHandler(async (req, res) => {
  const { agent_number, pause_type_code, reason } = req.body;
  const agent = await prisma.agent.findUnique({ where: { number: agent_number } });
  if (!agent) return res.status(404).json({ status: 'error', message: 'Agente no encontrado' });
  const pauseType = pause_type_code ? await prisma.pauseType.findUnique({ where: { code: pause_type_code } }) : null;
  const pause = await prisma.agentPause.create({
    data: { agentId: agent.id, pauseTypeId: pauseType?.id, reason },
  });
  res.json({ status: 'ok', pause_id: pause.id });
}));

/** POST /api/hotdesking/unpause */
router.post('/unpause', asyncHandler(async (req, res) => {
  const { agent_number } = req.body;
  const agent = await prisma.agent.findUnique({ where: { number: agent_number } });
  if (!agent) return res.status(404).json({ status: 'error', message: 'Agente no encontrado' });
  await prisma.agentPause.updateMany({
    where: { agentId: agent.id, endTime: null },
    data: { endTime: new Date() },
  });
  res.json({ status: 'ok' });
}));

export default router;
