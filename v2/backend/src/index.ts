import express from 'express';
import cors from 'cors';
import { env } from './lib/env.js';
import { autoSeed } from './lib/auto-seed.js';
import { startAmiHealthChecker, getAmiHealth } from './services/ami-health.js';
import { requireAuth } from './middleware/auth.js';

import authRoutes from './routes/auth.js';
import agentsRoutes from './routes/agents.js';
import callsRoutes from './routes/calls.js';
import cdrRoutes from './routes/cdr.js';
import dashboardRoutes from './routes/dashboard.js';
import extMetaRoutes from './routes/ext-meta.js';
import extensionsRoutes from './routes/extensions.js';
import groupsRoutes from './routes/groups.js';
import hotdeskingRoutes from './routes/hotdesking.js';
import ivrRoutes from './routes/ivr.js';
import queuesRoutes from './routes/queues.js';
import recordingsRoutes from './routes/recordings.js';
import reportsRoutes from './routes/reports.js';
import settingsRoutes from './routes/settings.js';

const app = express();

app.use(cors({ origin: env.CORS_ORIGIN, credentials: true }));
app.use(express.json({ limit: '5mb' }));

app.get('/api/health', (_req, res) => res.json({ ok: true, env: env.NODE_ENV }));

/**
 * Estado del AMI — protegido por auth.
 * - usuarios autenticados: ven `status` y `lastCheckedAt` (suficiente para el pill)
 * - solo admins: ven `message` y `hint` (detalles de infraestructura)
 */
app.get('/api/health/ami', requireAuth, (req, res) => {
  const h = getAmiHealth();
  const isAdmin = req.user?.role === 'admin';
  res.json({
    status: h.status,
    lastCheckedAt: h.lastCheckedAt,
    ...(isAdmin ? { message: h.message, hint: h.hint } : {}),
  });
});

app.use('/api/auth', authRoutes);
app.use('/api/agents', agentsRoutes);
app.use('/api/calls', callsRoutes);
app.use('/api/cdr', cdrRoutes);
app.use('/api/dashboard', dashboardRoutes);
app.use('/api/ext-meta', extMetaRoutes);
app.use('/api/extensions', extensionsRoutes);
app.use('/api/groups', groupsRoutes);
app.use('/api/hotdesking', hotdeskingRoutes);
app.use('/api/ivr', ivrRoutes);
app.use('/api/queues', queuesRoutes);
app.use('/api/recordings', recordingsRoutes);
app.use('/api/reports', reportsRoutes);
app.use('/api/settings', settingsRoutes);

app.use((err: any, _req: express.Request, res: express.Response, _next: express.NextFunction) => {
  // Errores con .status (ej. AsteriskUnavailableError) no spamean console
  if (err.status === 503) {
    return res.status(503).json({ error: err.message, code: 'ASTERISK_UNAVAILABLE' });
  }
  console.error('[error]', err);
  res.status(err.status || 500).json({ error: err.message || 'Error interno' });
});

autoSeed().catch((e) => console.error('[auto-seed] error:', e));
startAmiHealthChecker();

app.listen(env.PORT, () => {
  console.log(`[hznflow-backend] listening on http://localhost:${env.PORT}`);
  console.log(`[hznflow-backend] CORS allowed origin: ${env.CORS_ORIGIN}`);
});
