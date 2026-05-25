/**
 * Llamadas activas + acciones AMI (spy/whisper/barge/hangup/originate).
 */
import { Router } from 'express';
import { requireAuth } from '../middleware/auth.js';
import { asyncHandler } from '../lib/asyncHandler.js';
import { amiAction, hangup as amiHangup, originate as amiOriginate, spy as amiSpy } from '../services/asterisk-ami.js';

const router = Router();
router.use(requireAuth);

/** Lista de canales activos via AMI CoreShowChannels */
router.get('/active', asyncHandler(async (_req, res) => {
  try {
    const { getAmi, isAmiConnected } = await import('../services/asterisk-ami.js');
    if (!isAmiConnected()) {
      return res.status(503).json({ error: 'AMI no conectado', code: 'ASTERISK_UNAVAILABLE', channels: [] });
    }
    const client = getAmi();
    const events: any[] = [];

    await new Promise<void>((resolve) => {
      let done = false;
      const finish = () => {
        if (done) return;
        done = true;
        try { client.removeListener('coreshowchannel', onChan); } catch {}
        try { client.removeListener('coreshowchannelscomplete', onDone); } catch {}
        try { client.removeListener('managerevent', onAny); } catch {}
        resolve();
      };
      const onChan = (e: any) => events.push(e);
      const onDone = () => finish();
      // Algunas versiones de asterisk-manager solo emiten 'managerevent'
      const onAny = (e: any) => {
        const name = (e?.event || '').toLowerCase();
        if (name === 'coreshowchannel') events.push(e);
        if (name === 'coreshowchannelscomplete') finish();
      };

      client.on('coreshowchannel', onChan);
      client.on('coreshowchannelscomplete', onDone);
      client.on('managerevent', onAny);
      client.action({ Action: 'CoreShowChannels' }, () => {});
      setTimeout(finish, 3000);
    });

    res.json({ channels: events });
  } catch (e: any) {
    console.warn('[calls/active] AMI error:', e?.message);
    res.status(503).json({ error: 'Error AMI', code: 'ASTERISK_UNAVAILABLE', channels: [] });
  }
}));

router.post('/hangup', asyncHandler(async (req, res) => {
  const { channel } = req.body;
  if (!channel) return res.status(400).json({ error: 'channel requerido' });
  const r = await amiHangup(channel);
  res.json({ ok: true, ami: r });
}));

router.post('/originate', asyncHandler(async (req, res) => {
  const r = await amiOriginate(req.body);
  res.json({ ok: true, ami: r });
}));

router.post('/spy', asyncHandler(async (req, res) => {
  const { myExt, targetChannel, mode } = req.body;
  if (!myExt || !targetChannel) return res.status(400).json({ error: 'myExt y targetChannel requeridos' });
  const r = await amiSpy({ myExt, targetChannel, mode });
  res.json({ ok: true, ami: r });
}));

/** Acción libre AMI (escape hatch) */
router.post('/ami-action', asyncHandler(async (req, res) => {
  const r = await amiAction(req.body);
  res.json({ ok: true, ami: r });
}));

export default router;
