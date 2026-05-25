/**
 * Helpers compartidos para mostrar info de llamadas.
 * Traducción de "disposition" (cómo terminó la llamada) al español.
 */

export type Disposition = 'ANSWERED' | 'NO ANSWER' | 'BUSY' | 'FAILED' | string;

/** Traduce el código en inglés del CDR al label en español */
export function dispositionLabel(d: Disposition): string {
  const map: Record<string, string> = {
    'ANSWERED': 'Atendida',
    'NO ANSWER': 'No atendida',
    'BUSY': 'Ocupado',
    'FAILED': 'Falló',
  };
  return map[d] || d;
}

/** Variante de Badge según el resultado */
export function dispositionVariant(d: Disposition): 'success' | 'warning' | 'destructive' | 'secondary' {
  if (d === 'ANSWERED') return 'success';
  if (d === 'NO ANSWER') return 'warning';
  if (d === 'BUSY' || d === 'FAILED') return 'destructive';
  return 'secondary';
}

/** Lista de resultados posibles (para selects/filtros) */
export const DISPOSITION_OPTIONS: { value: Disposition; label: string }[] = [
  { value: 'ANSWERED', label: 'Atendida' },
  { value: 'NO ANSWER', label: 'No atendida' },
  { value: 'BUSY', label: 'Ocupado' },
  { value: 'FAILED', label: 'Falló' },
];
