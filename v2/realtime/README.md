# Teleflow Realtime Hub

Este servicio conecta el panel de Teleflow con Asterisk en tiempo real usando el protocolo AMI.

## Instalación

1.  Abre una terminal en este directorio (`c:\Users\Flavio\Documents\EXPRESS\issabel\realtime`).
2.  Ejecuta el comando para instalar las dependencias:
    ```bash
    npm install
    ```

## Configuración

El archivo `index.js` ya tiene configurados los valores por defecto. Si el usuario de AMI de tu Issabel es diferente a `admin` o la clave no es `Sildan.1329`, puedes editarlos en las primeras líneas de `index.js`.

## Ejecución

Para iniciar el hub, ejecuta:
```bash
node index.js
```

Una vez iniciado, verás el mensaje `✅ Conectado a Asterisk AMI`. El Radar en el navegador se conectará automáticamente al puerto 3001.
