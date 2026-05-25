import { PrismaClient } from '@prisma/client';
import bcrypt from 'bcryptjs';

const prisma = new PrismaClient();

async function main() {
  // Admin inicial — mismo password que en el PHP (DEFAULT_ADMIN_PASS)
  const adminPass = 'RK-ARrSKsefUOqyszL-p';
  const passwordHash = await bcrypt.hash(adminPass, 10);

  await prisma.user.upsert({
    where: { username: 'admin' },
    update: {},
    create: {
      username: 'admin',
      passwordHash,
      role: 'admin',
      fullName: 'Administrador',
      active: true,
    },
  });
  console.log('✓ admin user ready (user: admin / pass: RK-ARrSKsefUOqyszL-p)');

  // Pause types default
  const pauseTypes = [
    { code: 'BREAK', label: 'Receso', color: '#3b82f6', billable: false },
    { code: 'LUNCH', label: 'Almuerzo', color: '#f59e0b', billable: false },
    { code: 'BATH', label: 'Baño', color: '#8b5cf6', billable: false },
    { code: 'MEETING', label: 'Reunión', color: '#10b981', billable: true },
    { code: 'TRAINING', label: 'Capacitación', color: '#ec4899', billable: true },
  ];
  for (const pt of pauseTypes) {
    await prisma.pauseType.upsert({
      where: { code: pt.code },
      update: {},
      create: pt,
    });
  }
  console.log(`✓ ${pauseTypes.length} pause types ready`);

  // Branding defaults
  const settings = [
    { key: 'brand_app_name', value: 'HznFlow' },
    { key: 'brand_company_name', value: 'Horizon Seguridad' },
    { key: 'brand_logo_text', value: 'HznFlow' },
    { key: 'brand_logo_sub', value: 'Control de Portería' },
  ];
  for (const s of settings) {
    await prisma.appSetting.upsert({
      where: { key: s.key },
      update: {},
      create: s,
    });
  }
  console.log(`✓ ${settings.length} default settings ready`);
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
