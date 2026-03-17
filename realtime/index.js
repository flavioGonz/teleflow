const io = require('socket.io')(3001, {
    cors: { origin: "*" },
    path: '/teleflow-socket'
});
const AsteriskManager = require('asterisk-manager');
require('dotenv').config();

// Configuración (Se recomienda usar un archivo .env)
const AMI_PORT = process.env.AMI_PORT || 5038;
const AMI_HOST = process.env.AMI_HOST || 'localhost';
const AMI_USER = process.env.AMI_USER || 'admin';
const AMI_PASS = process.env.AMI_PASS || 'Sildan.1329'; // Usando el password de la DB como fallback común

const ami = new AsteriskManager(AMI_PORT, AMI_HOST, AMI_USER, AMI_PASS, true);

// Estado en memoria de las llamadas activas
let activeCalls = {};

console.log('🚀 Teleflow Realtime Hub iniciando...');

ami.on('managerevent', evt => {
    // Filtrar eventos relevantes para el Radar
    const event = evt.event.toLowerCase();
    
    // 1. Nueva llamada / Canal creado
    if (event === 'newchannel') {
        const channelId = evt.uniqueid;
        activeCalls[channelId] = {
            id: channelId,
            channel: evt.channel,
            ext: evt.calleridnum,
            name: evt.calleridname,
            state: evt.channelstateheader || 'Down',
            dest: evt.exten,
            context: evt.context,
            startTime: Date.now(),
            linkedId: evt.linkedid || channelId,
            isBridged: false
        };
        io.emit('call_update', { type: 'new', call: activeCalls[channelId] });
    }

    // 2. Cambio de estado (Ringing, Up, etc)
    if (event === 'newstate') {
        const channelId = evt.uniqueid;
        if (activeCalls[channelId]) {
            activeCalls[channelId].state = evt.channelstateheader;
            io.emit('call_update', { type: 'state', id: channelId, state: evt.channelstateheader });
        }
    }

    // 3. Puenteado (Hablando)
    if (event === 'bridgeenter') {
        const channelId = evt.uniqueid;
        if (activeCalls[channelId]) {
            activeCalls[channelId].isBridged = true;
            activeCalls[channelId].bridgeId = evt.bridgeuniqueid;
            io.emit('call_update', { type: 'bridge', id: channelId, bridgeId: evt.bridgeuniqueid });
        }
    }

    // 4. Fin de llamada
    if (event === 'hangup') {
        const channelId = evt.uniqueid;
        if (activeCalls[channelId]) {
            io.emit('call_update', { type: 'hangup', id: channelId });
            delete activeCalls[channelId];
        }
    }
});

io.on('connection', (socket) => {
    console.log('📱 Cliente conectado:', socket.id);
    // Enviar estado actual al conectar
    socket.emit('initial_state', activeCalls);
});

ami.on('connect', () => console.log('✅ Conectado a Asterisk AMI'));
ami.on('error', err => console.error('❌ Error AMI:', err));

ami.keepConnected();
