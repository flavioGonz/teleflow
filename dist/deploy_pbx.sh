#!/bin/bash
# TeleFlow — Deploy v4: pause/unpause + login a cola específica
set -e
VM=http://10.1.1.192/dist
SOUNDS=/var/lib/asterisk/sounds/custom

echo "1) Bajando dialplan v4..."
curl -s -o /etc/asterisk/extensions_teleflow.conf $VM/extensions_teleflow.conf
chown asterisk:asterisk /etc/asterisk/extensions_teleflow.conf
chmod 644 /etc/asterisk/extensions_teleflow.conf

echo "2) Bajando audios..."
mkdir -p $SOUNDS
for f in teleflow-pide-numero teleflow-pide-clave teleflow-acceso-correcto teleflow-acceso-denegado teleflow-logout-ok teleflow-pide-motivo teleflow-pausa-activada teleflow-pausa-finalizada teleflow-no-logueado; do
    curl -s -o $SOUNDS/${f}.wav $VM/${f}.wav
done
chown asterisk:asterisk $SOUNDS/teleflow-*.wav
chmod 644 $SOUNDS/teleflow-*.wav

echo "3) Bajando AGIs..."
curl -s -o /var/lib/asterisk/agi-bin/teleflow_agent_login.agi  $VM/teleflow_agent_login.agi
curl -s -o /var/lib/asterisk/agi-bin/teleflow_agent_logout.agi $VM/teleflow_agent_logout.agi
curl -s -o /var/lib/asterisk/agi-bin/teleflow_agi.conf.example $VM/teleflow_agi.conf.example
chown asterisk:asterisk /var/lib/asterisk/agi-bin/teleflow_agent_*.agi
chmod 755 /var/lib/asterisk/agi-bin/teleflow_agent_*.agi


echo "4b) Configurando teleflow_agi.conf en /etc/asterisk..."
if [ ! -f /etc/asterisk/teleflow_agi.conf ]; then
    cp /var/lib/asterisk/agi-bin/teleflow_agi.conf.example /etc/asterisk/teleflow_agi.conf 2>/dev/null || \
    curl -s -o /etc/asterisk/teleflow_agi.conf $VM/teleflow_agi.conf.example
    chmod 600 /etc/asterisk/teleflow_agi.conf
    chown asterisk:asterisk /etc/asterisk/teleflow_agi.conf
    echo "  ⚠ Editá /etc/asterisk/teleflow_agi.conf con las credenciales reales antes de probar"
fi

echo "4) Verificando include..."
if ! grep -q extensions_teleflow.conf /etc/asterisk/extensions_custom.conf 2>/dev/null; then
    echo "" >> /etc/asterisk/extensions_custom.conf
    echo "; TeleFlow feature codes" >> /etc/asterisk/extensions_custom.conf
    echo "#include extensions_teleflow.conf" >> /etc/asterisk/extensions_custom.conf
fi

echo "5) Reload dialplan..."
asterisk -rx "dialplan reload" >/dev/null
echo ""
echo "✓ DEPLOY OK — Feature codes activos:"
echo "  • *7700        Login (con prefs guardadas)"
echo "  • *7700*<Q>    Login solo a cola Q (ej: *7700*9100)"
echo "  • *7701        Logout total"
echo "  • *7702        Pausar (pide motivo: 1=lunch 2=break 3=baño 4=reunión 5=training 6=personal)"
echo "  • *7703        Despausar"
