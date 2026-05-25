import { Settings, Construction } from 'lucide-react';
import { Card, CardContent } from '../components/ui/card';

export default function Configuracion() {
  return (
    <div className="space-y-6 max-w-3xl">
      <div>
        <h1 className="text-2xl font-bold tracking-tight flex items-center gap-2">
          <Settings className="h-6 w-6 text-primary" />
          Configuración
        </h1>
        <p className="text-sm text-muted-foreground mt-1">
          Parámetros de la aplicación y preferencias.
        </p>
      </div>

      <Card className="border-dashed">
        <CardContent className="p-8 text-center">
          <Construction className="h-10 w-10 mx-auto text-muted-foreground opacity-50 mb-3" />
          <h3 className="font-semibold text-base mb-1">Sección en construcción</h3>
          <p className="text-sm text-muted-foreground max-w-md mx-auto">
            Pronto vas a poder configurar acá: notificaciones, sonidos, atajos de teclado,
            tema, auto-refresh, integraciones, parámetros del PBX y políticas de seguridad.
          </p>
        </CardContent>
      </Card>
    </div>
  );
}
