/**
 * TeleFlow Realtime Hub v2 — Horizon Seguridad
 * - AMI events → Socket.io broadcast (live)
 * - AMI events → MySQL persistence (queue_events, agent_pauses)
 */

const path = require('path');
try { require('dotenv').config({ path: path.join(__dirname, '.env') }); } catch(e) {}

const AMI_HOST = process.env.AMI_HOST || '10.1.1.7';
const AMI_PORT = parseInt(process.env.AMI_PORT || '5038', 10);
const AMI_USER = process.env.AMI_USER || 'admin';
const AMI_PASS = process.env.AMI_PASS || '';
const WS_PORT  = parseInt(process.env.WS_PORT || '3001', 10);
const DB_HOST  = process.env.DB_HOST || '10.1.1.7';
const DB_USER  = process.env.DB_USER || 'tfremote';
const DB_PASS  = process.env.DB_PASS || '';
const DB_NAME  = process.env.DB_NAME || 'teleflow';

const AsteriskManager = require('asterisk-manager');
const io = require('socket.io')(WS_PORT, { cors: { origin: '*' }, path: '/teleflow-socket', serveClient: true });
let mysql; try { mysql = require('mysql2/promise'); } catch(e) { console.error('[realtime] mysql2 not installed — DB persistence DISABLED. Run: npm install mysql2'); }

console.log(`[realtime v2] starting — AMI ${AMI_HOST}:${AMI_PORT} as ${AMI_USER}; WS :${WS_PORT}/teleflow-socket; DB ${DB_HOST}/${DB_NAME}`);

// ───── DB pool ─────────────────────────────────────────────
let pool = null;
if (mysql) {
    pool = mysql.createPool({ host: DB_HOST, user: DB_USER, password: DB_PASS, database: DB_NAME, connectionLimit: 5, waitForConnections: true });
    pool.query('SELECT 1').then(() => console.log('[realtime] DB connected')).catch(e => console.error('[realtime] DB error:', e.message));
}

async function dbExec(sql, params) {
    if (!pool) return null;
    try { const [r] = await pool.execute(sql, params); return r; } 
    catch(e) { console.error('[db]', e.message, sql.substring(0,80)); return null; }
}

// ───── In-memory state ─────────────────────────────────────
const state = {
    calls: {},      // uniqueid → call
    peers: {},      // ext → {status,ip,rtt}
    members: {},    // queue → { agent → status }
    queueWaiting: {} // uniqueid → {queue, callerid, joinTime}
};

const extFromChannel = (chan) => {
    if (!chan) return null;
    const m = chan.match(/^(?:PJSIP|SIP|Local)\/(\d+)/i);
    return m ? m[1] : null;
};

// ───── AMI ─────────────────────────────────────────────────
const ami = new AsteriskManager(AMI_PORT, AMI_HOST, AMI_USER, AMI_PASS, true);
ami.keepConnected();

ami.on('connect', () => console.log('[realtime] AMI connected'));
ami.on('disconnect', () => console.log('[realtime] AMI disconnected — reconnecting...'));
ami.on('error', err => console.error('[realtime] AMI error:', err.message || err));
ami.on('fullybooted', () => { console.log('[realtime] AMI booted — bootstrap'); bootstrap(); });

function bootstrap() {
    ami.action({ Action: 'SIPpeers' }, () => {});
    ami.action({ Action: 'PJSIPShowEndpoints' }, () => {});
    ami.action({ Action: 'QueueStatus' }, () => {});
    ami.action({ Action: 'CoreShowChannels' }, () => {});
}
setInterval(bootstrap, 5 * 60 * 1000);
setInterval(() => io.emit('heartbeat', { ts: Date.now() }), 10000);

// ───── AMI events ──────────────────────────────────────────
ami.on('managerevent', async (evt) => {
    const E = (evt.event || '').toLowerCase();

    switch (E) {
        case 'newchannel': {
            const id = evt.uniqueid;
            state.calls[id] = {
                id, channel: evt.channel,
                ext: evt.calleridnum, name: evt.calleridname,
                state: evt.channelstatedesc || evt.channelstate,
                exten: evt.exten, context: evt.context,
                startTime: Date.now(), linkedId: evt.linkedid || id,
                isBridged: false
            };
            io.emit('call_update', { type: 'new', call: state.calls[id] });
            const ext = extFromChannel(evt.channel);
            if (ext) { state.peers[ext] = { ...(state.peers[ext]||{}), status:'BUSY' }; io.emit('peer_update', { ext, status:'BUSY' }); }
            break;
        }
        case 'newstate': {
            const id = evt.uniqueid;
            if (state.calls[id]) {
                state.calls[id].state = evt.channelstatedesc || evt.channelstate;
                io.emit('call_update', { type: 'state', id, state: state.calls[id].state });
            }
            break;
        }
        case 'bridgeenter': {
            const id = evt.uniqueid;
            if (state.calls[id]) {
                state.calls[id].isBridged = true;
                state.calls[id].bridgeId = evt.bridgeuniqueid;
                io.emit('call_update', { type: 'bridge', id, bridgeId: evt.bridgeuniqueid });
            }
            break;
        }
        case 'bridgeleave': {
            const id = evt.uniqueid;
            if (state.calls[id]) { state.calls[id].isBridged = false; io.emit('call_update', { type: 'unbridge', id }); }
            break;
        }
        case 'hangup': {
            const id = evt.uniqueid;
            if (state.calls[id]) {
                io.emit('call_update', { type: 'hangup', id, channel: state.calls[id].channel });
                const ext = extFromChannel(state.calls[id].channel);
                delete state.calls[id];
                if (ext) {
                    const stillInCall = Object.values(state.calls).some(c => extFromChannel(c.channel) === ext);
                    if (!stillInCall) { state.peers[ext] = { ...(state.peers[ext]||{}), status:'ONLINE' }; io.emit('peer_update', { ext, status:'ONLINE' }); }
                }
            }
            break;
        }
        case 'peerstatus': {
            const ext = (evt.peer || '').replace(/^(?:PJSIP|SIP)\//, '').split('/')[0];
            if (!ext) break;
            const status = (evt.peerstatus || '').toUpperCase();
            const mapped = (status === 'REGISTERED' || status === 'REACHABLE') ? 'ONLINE'
                         : (status === 'UNREACHABLE' || status === 'UNREGISTERED' || status === 'LAGGED') ? 'OFFLINE' : 'OFFLINE';
            state.peers[ext] = { ...(state.peers[ext]||{}), status: mapped };
            if (evt.address) state.peers[ext].ip = evt.address.split(':')[0];
            io.emit('peer_update', { ext, status: mapped, ip: state.peers[ext].ip });
            break;
        }

        // ───── Queue Events (con persistencia) ────────────
        case 'queuecallerjoin': {
            io.emit('queue_update', { type: 'join', queue: evt.queue, callerid: evt.calleridnum, position: evt.position });
            state.queueWaiting[evt.uniqueid] = { queue: evt.queue, callerid: evt.calleridnum, joinTime: Date.now() };
            await dbExec(
                "INSERT INTO queue_events (queue_name, event_type, caller_id) VALUES (?, 'JOIN', ?)",
                [evt.queue, evt.calleridnum || '']
            );
            break;
        }
        case 'queuecallerabandon': {
            io.emit('queue_update', { type: 'abandon', queue: evt.queue, callerid: evt.calleridnum });
            const waitInfo = state.queueWaiting[evt.uniqueid];
            const waitSec = waitInfo ? Math.floor((Date.now() - waitInfo.joinTime)/1000) : null;
            delete state.queueWaiting[evt.uniqueid];
            await dbExec(
                "INSERT INTO queue_events (queue_name, event_type, caller_id, wait_time) VALUES (?, 'ABANDON', ?, ?)",
                [evt.queue, evt.calleridnum || '', waitSec]
            );
            break;
        }
        case 'agentconnect': {
            io.emit('queue_update', { type: 'connect', queue: evt.queue, member: evt.interface });
            const agentExt = extFromChannel(evt.interface || evt.membername || '');
            const waitInfo = state.queueWaiting[evt.uniqueid];
            const waitSec = waitInfo ? Math.floor((Date.now() - waitInfo.joinTime)/1000) : (parseInt(evt.holdtime) || null);
            if (waitInfo) state.queueWaiting[evt.uniqueid].connectTime = Date.now();
            await dbExec(
                "INSERT INTO queue_events (queue_name, event_type, agent_ext, caller_id, wait_time) VALUES (?, 'CONNECT', ?, ?, ?)",
                [evt.queue, agentExt, evt.calleridnum || '', waitSec]
            );
            break;
        }
        case 'agentcomplete': {
            const agentExt = extFromChannel(evt.interface || evt.membername || '');
            const waitInfo = state.queueWaiting[evt.uniqueid];
            const talkSec = waitInfo?.connectTime ? Math.floor((Date.now() - waitInfo.connectTime)/1000) : (parseInt(evt.talktime) || null);
            delete state.queueWaiting[evt.uniqueid];
            await dbExec(
                "INSERT INTO queue_events (queue_name, event_type, agent_ext, caller_id, talk_time) VALUES (?, 'COMPLETE', ?, ?, ?)",
                [evt.queue, agentExt, evt.calleridnum || '', talkSec]
            );
            break;
        }

        // ───── Agent pause persistence ────────────────────
        case 'queuememberpause': {
            const queue = evt.queue;
            const member = evt.interface || evt.membername;
            if (!queue || !member) break;
            state.members[queue] = state.members[queue] || {};
            const m = state.members[queue][member] = state.members[queue][member] || {};
            const paused = (evt.paused === '1' || evt.paused === 1 || evt.paused === true);
            m.paused = paused;
            io.emit('queue_update', { type: 'pause', queue, member, paused });
            // HORIZON: la persistencia en agent_pauses la hace agent_pause_commit.php / agent_unpause_commit.php
            // (porque la action QueuePause con Reason no propaga el motivo al event QueueMemberPause).
            // El hub queda como broadcaster, sin doble-INSERT.
            break;
        }
        case 'queuememberstatus':
        case 'queuememberadded':
        case 'queuememberremoved': {
            const queue = evt.queue;
            const member = evt.interface || evt.membername;
            if (!queue || !member) break;
            state.members[queue] = state.members[queue] || {};
            const m = state.members[queue][member] = state.members[queue][member] || {};
            if (evt.status !== undefined) m.status = evt.status;
            if (E === 'queuememberremoved') {
                delete state.members[queue][member];
                io.emit('queue_update', { type: 'remove', queue, member });
            } else {
                io.emit('queue_update', { type: E.replace('queuemember',''), queue, member, status: m.status });
            }
            break;
        }
    }
});

// ───── Socket.io clients ───────────────────────────────────
io.on('connection', (socket) => {
    console.log(`[realtime] client connected: ${socket.id}`);
    socket.emit('initial_state', { calls: state.calls, peers: state.peers, members: state.members });
    socket.on('disconnect', () => console.log(`[realtime] client disconnected: ${socket.id}`));
});


// ───── HTTP /broadcast endpoint para que AGI/PHP notifiquen eventos ──
const http = require('http');
const broadcastServer = http.createServer((req, res) => {
    if (req.method === 'POST' && req.url === '/broadcast') {
        let body = '';
        req.on('data', c => body += c);
        req.on('end', () => {
            try {
                const tok = req.headers['x-tf-token'] || '';
                const expected = process.env.TF_BROADCAST_TOKEN || '';
                if (!expected || tok !== expected) { res.writeHead(403); return res.end('forbidden'); }
                const j = JSON.parse(body || '{}');
                const event = j.event || 'tf-event';
                const data = j.data || {};
                console.log(`[broadcast] ${event}`, JSON.stringify(data).substring(0,120));
                io.emit(event, data);
                io.emit('tf-realtime-refresh', { source: event, data });  // catch-all
                res.writeHead(200, {'Content-Type':'application/json'});
                res.end(JSON.stringify({ok:true,event}));
            } catch(e) {
                res.writeHead(400); res.end('bad request');
            }
        });
    } else {
        res.writeHead(404); res.end('not found');
    }
});
broadcastServer.listen(9001, '127.0.0.1', () => console.log('[realtime] broadcast HTTP listening on :9001'));

function shutdown() {
    console.log('[realtime] shutting down...');
    try { ami.disconnect(); } catch(e){}
    try { io.close(); } catch(e){}
    try { pool && pool.end(); } catch(e){}
    process.exit(0);
}
process.on('SIGTERM', shutdown);
process.on('SIGINT', shutdown);
