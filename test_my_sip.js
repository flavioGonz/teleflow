const WebSocket = require('ws');
const crypto = require('crypto');

const ws = new WebSocket('wss://201.217.134.124:8089/ws', 'sip', {
    rejectUnauthorized: false
});

let callid = 'test_callid_' + Math.random().toString(36).substring(7);
let branch = 'z9hG4bK_' + Math.random().toString(36).substring(7);
let tag = Math.random().toString(36).substring(7);

function sendRegister(cseq, authHeader = '') {
    const msg = `REGISTER sip:201.217.134.124 SIP/2.0\r
Via: SIP/2.0/WSS 127.0.0.1;branch=${branch}\r
Max-Forwards: 70\r
From: <sip:2005@201.217.134.124>;tag=${tag}\r
To: <sip:2005@201.217.134.124>\r
Call-ID: ${callid}\r
CSeq: ${cseq} REGISTER\r
Contact: <sip:2005@127.0.0.1;transport=ws>\r
Expires: 600\r
User-Agent: NodeSipTest\r
${authHeader ? authHeader + '\r\n' : ''}Content-Length: 0\r\n\r\n`;
    console.log("SENDING:\n", msg);
    ws.send(msg);
}

ws.on('open', () => {
    console.log("Connected to WSS");
    sendRegister(1);
});

ws.on('message', (data) => {
    const resp = data.toString();
    console.log("RECEIVED:\n", resp);
    
    if (resp.includes("401 Unauthorized")) {
        // extract WWW-Authenticate
        const match = resp.match(/WWW-Authenticate: Digest (.*)/);
        if (match) {
            const params = {};
            match[1].split(',').forEach(p => {
                const parts = p.trim().split('=');
                params[parts[0]] = parts[1].replace(/"/g, '');
            });
            console.log("Challenge params:", params);
            
            // Calculate MD5
            const username = '2005';
            const password = 'teleflow123';
            const realm = params.realm;
            const nonce = params.nonce;
            const uri = 'sip:201.217.134.124';
            
            const a1 = crypto.createHash('md5').update(`${username}:${realm}:${password}`).digest('hex');
            const a2 = crypto.createHash('md5').update(`REGISTER:${uri}`).digest('hex');
            let response;
            
            if (params.qop === 'auth') {
                const nc = '00000001';
                const cnonce = Math.random().toString(36).substring(7);
                response = crypto.createHash('md5').update(`${a1}:${nonce}:${nc}:${cnonce}:auth:${a2}`).digest('hex');
                
                const authHeader = `Authorization: Digest username="${username}", realm="${realm}", nonce="${nonce}", uri="${uri}", response="${response}", algorithm=MD5, cnonce="${cnonce}", opaque="${params.opaque}", qop=auth, nc=${nc}`;
                sendRegister(2, authHeader);
            } else {
                response = crypto.createHash('md5').update(`${a1}:${nonce}:${a2}`).digest('hex');
                const authHeader = `Authorization: Digest username="${username}", realm="${realm}", nonce="${nonce}", uri="${uri}", response="${response}", algorithm=MD5`;
                sendRegister(2, authHeader);
            }
        }
    } else if (resp.includes("200 OK")) {
        console.log("SUCCESS");
        ws.close();
    }
});

ws.on('error', (err) => console.error("WS Error:", err));
