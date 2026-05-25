/**
 * Auto-seed en startup: si no hay ningún usuario, crea el admin inicial.
 * Idempotente — si ya hay usuarios, no toca nada.
 */
import bcrypt from 'bcryptjs';
import { prisma } from './prisma.js';

const DEFAULT_ADMIN_USER = 'admin';
const DEFAULT_ADMIN_PASS = 'RK-ARrSKsefUOqyszL-p';

export async function autoSeed() {
  const userCount = await prisma.user.count();
  if (userCount === 0) {
    const hash = await bcrypt.hash(DEFAULT_ADMIN_PASS, 10);
    await prisma.user.create({
      data: {
        username: DEFAULT_ADMIN_USER,
        passwordHash: hash,
        role: 'admin',
        fullName: 'Administrador',
        active: true,
      },
    });
    console.log(`[auto-seed] admin creado (user: ${DEFAULT_ADMIN_USER} / pass: ${DEFAULT_ADMIN_PASS})`);
  }

  const ptCount = await prisma.pauseType.count();
  if (ptCount === 0) {
    await prisma.pauseType.createMany({
      data: [
        { code: 'BREAK', label: 'Receso', color: '#3b82f6' },
        { code: 'LUNCH', label: 'Almuerzo', color: '#f59e0b' },
        { code: 'BATH', label: 'Baño', color: '#8b5cf6' },
        { code: 'MEETING', label: 'Reunión', color: '#10b981', billable: true },
        { code: 'TRAINING', label: 'Capacitación', color: '#ec4899', billable: true },
      ],
    });
    console.log('[auto-seed] pause_types defaults');
  }

  // Nota: branding (nombre app/empresa/logo) NO va en DB.
  // Vive en frontend/src/lib/brand.ts como constantes fijas.
}
