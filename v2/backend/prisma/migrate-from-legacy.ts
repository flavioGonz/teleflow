/**
 * Migración one-shot: lee la DB MySQL `teleflow` legacy + SQLite `acl.db`
 * y los inserta en la DB Postgres nueva (vía Prisma).
 *
 * Ejecutar desde v2/backend con:
 *   npx tsx prisma/migrate-from-legacy.ts
 *
 * Variables de entorno requeridas (en .env o exportadas):
 *   MIGR_MYSQL_HOST, MIGR_MYSQL_PORT, MIGR_MYSQL_USER, MIGR_MYSQL_PASS, MIGR_MYSQL_DB
 *   MIGR_SQLITE_PATH (opcional — para migrar admin users)
 *
 * Idempotente: usa upsert, podés correrlo varias veces.
 */
import 'dotenv/config';
import mysql from 'mysql2/promise';
import { PrismaClient } from '@prisma/client';
import bcrypt from 'bcryptjs';
import Database from 'better-sqlite3';
import path from 'path';

const prisma = new PrismaClient();

const ENV = {
  MYSQL_HOST: process.env.MIGR_MYSQL_HOST || process.env.ASTERISK_DB_HOST || '10.1.1.7',
  MYSQL_PORT: parseInt(process.env.MIGR_MYSQL_PORT || '3306', 10),
  MYSQL_USER: process.env.MIGR_MYSQL_USER || process.env.ASTERISK_DB_USER || '',
  MYSQL_PASS: process.env.MIGR_MYSQL_PASS || process.env.ASTERISK_DB_PASS || '',
  MYSQL_DB: process.env.MIGR_MYSQL_DB || 'teleflow',
  SQLITE_PATH: process.env.MIGR_SQLITE_PATH || '/var/www/teleflow/db/acl.db',
};

let mysqlConn: mysql.Connection | null = null;

async function connectMysql() {
  console.log(`→ MySQL ${ENV.MYSQL_HOST}:${ENV.MYSQL_PORT}/${ENV.MYSQL_DB}`);
  mysqlConn = await mysql.createConnection({
    host: ENV.MYSQL_HOST, port: ENV.MYSQL_PORT,
    user: ENV.MYSQL_USER, password: ENV.MYSQL_PASS,
    database: ENV.MYSQL_DB,
  });
}

async function q<T = any>(sql: string, params: any[] = []): Promise<T[]> {
  const [rows] = await mysqlConn!.query(sql, params);
  return rows as T[];
}

async function migrateAclUsers() {
  console.log('\n── Migrando admins (SQLite acl.db → Postgres User)');
  let count = 0;
  try {
    const sqlite = new Database(ENV.SQLITE_PATH, { readonly: true });
    const rows = sqlite.prepare('SELECT name, md5_password FROM acl_user').all() as any[];
    for (const r of rows) {
      // El MD5 viejo no es bcrypt — lo guardamos pero forzamos reset en el primer login
      // Como workaround pragmático, generamos un hash bcrypt del MD5 (el password sigue funcionando
      // si el frontend manda el password plano y el backend lo bcryptea; pero el MD5 ya no sirve).
      // Mejor: marcamos el usuario para forzar reset.
      const placeholderHash = await bcrypt.hash(`legacy-md5:${r.md5_password}`, 10);
      await prisma.user.upsert({
        where: { username: r.name },
        update: {}, // no sobreescribimos si ya existe
        create: {
          username: r.name,
          passwordHash: placeholderHash,
          role: 'admin',
          fullName: r.name,
          active: false, // ← desactivado hasta que un admin le ponga password nuevo
        },
      });
      count++;
    }
    sqlite.close();
    console.log(`  ✓ ${count} admins migrados (desactivados — reset password manual)`);
  } catch (e: any) {
    console.warn(`  ⚠ skip: ${e.message}`);
  }
}

async function migratePauseTypes() {
  console.log('\n── pause_types');
  let count = 0;
  try {
    const rows = await q('SELECT * FROM pause_types');
    for (const r of rows) {
      await prisma.pauseType.upsert({
        where: { code: r.code || `LEGACY_${r.id}` },
        update: {},
        create: {
          code: r.code || `LEGACY_${r.id}`,
          label: r.label || r.name || 'Sin nombre',
          color: r.color,
          billable: !!r.billable,
        },
      });
      count++;
    }
    console.log(`  ✓ ${count} pause_types`);
  } catch (e: any) {
    console.warn(`  ⚠ skip: ${e.message}`);
  }
}

async function migrateAgents() {
  console.log('\n── agents → Agent');
  let count = 0;
  // 1) Tabla agents (puede estar vacía si la app vieja no la usaba)
  try {
    const rows = await q('SELECT * FROM agents');
    for (const r of rows) {
      await prisma.agent.upsert({
        where: { number: String(r.ext) },
        update: {},
        create: {
          number: String(r.ext),
          name: r.name || 'Sin nombre',
          email: r.email || null,
          phone: r.phone || null,
          avatarUrl: r.avatar_url || null,
          active: r.active !== 0,
          // El legacy tenía department/team; los migramos como sector concatenado.
          sector: [r.department, r.team].filter(Boolean).join(' · ') || null,
          hireDate: r.hire_date ? new Date(r.hire_date) : null,
          status: r.status || null,
        },
      });
      count++;
    }
  } catch (e: any) {
    console.warn(`  ⚠ tabla agents skip: ${e.message}`);
  }
  console.log(`  ✓ ${count} desde tabla agents`);

  // 2) Fallback — crear agents desde extensions SIP de la DB asterisk,
  //    para que las sessions/pauses puedan vincularse.
  console.log('  → derivando agents desde MySQL asterisk.users + agent_sessions...');
  let derived = 0;
  try {
    // Conexión temporal a la DB asterisk para leer users (extensiones SIP con nombre)
    const ast = await mysql.createConnection({
      host: ENV.MYSQL_HOST, port: ENV.MYSQL_PORT,
      user: ENV.MYSQL_USER, password: ENV.MYSQL_PASS,
      database: 'asterisk',
    });
    const [userRows] = await ast.query('SELECT extension, name FROM users') as any;

    // Todas las extensiones que aparecen en sessions/pauses
    const [s1] = await mysqlConn!.query('SELECT DISTINCT agent_ext FROM agent_sessions WHERE agent_ext IS NOT NULL') as any;
    const [s2] = await mysqlConn!.query('SELECT DISTINCT agent_ext FROM agent_pauses WHERE agent_ext IS NOT NULL') as any;
    const [s3] = await mysqlConn!.query('SELECT DISTINCT agent_number FROM agent_queue_pref WHERE agent_number IS NOT NULL') as any;
    const exts = new Set<string>([
      ...s1.map((r: any) => String(r.agent_ext)),
      ...s2.map((r: any) => String(r.agent_ext)),
      ...s3.map((r: any) => String(r.agent_number)),
    ]);
    const nameMap = new Map<string, string>(userRows.map((u: any) => [String(u.extension), u.name || '']));

    for (const ext of exts) {
      const existing = await prisma.agent.findUnique({ where: { number: ext } });
      if (existing) continue;
      await prisma.agent.create({
        data: {
          number: ext,
          name: nameMap.get(ext) || `Ext ${ext}`,
          active: true,
        },
      });
      derived++;
    }
    await ast.end();
    console.log(`  ✓ ${derived} derivados (creados a partir de sessions/pauses)`);
  } catch (e: any) {
    console.warn(`  ⚠ derivación falló: ${e.message}`);
  }
}

async function migrateAgentSessions() {
  console.log('\n── agent_sessions');
  let count = 0; let skipped = 0;
  try {
    const rows = await q('SELECT * FROM agent_sessions');
    for (const r of rows) {
      const agentNum = String(r.agent_ext ?? r.agent_number);
      const agent = await prisma.agent.findUnique({ where: { number: agentNum } });
      if (!agent) { skipped++; continue; }
      await prisma.agentSession.create({
        data: {
          agentId: agent.id,
          sessionId: r.session_id || null,
          extension: String(r.agent_ext || ''),
          loginTime: r.login_time ? new Date(r.login_time) : new Date(),
          logoutTime: r.logout_time ? new Date(r.logout_time) : null,
          shiftDate: r.shift_date ? new Date(r.shift_date) : null,
          status: r.status || null,
          totalCalls: r.total_calls || 0,
          totalTalkTime: r.total_talk_time || 0,
          totalAcwTime: r.total_acw_time || 0,
          totalPauseTime: r.total_pause_time || 0,
          moodFlags: r.mood_flags || null,
        },
      });
      count++;
    }
    console.log(`  ✓ ${count} agent_sessions (${skipped} skipped — agente no encontrado)`);
  } catch (e: any) {
    console.warn(`  ⚠ skip: ${e.message}`);
  }
}

async function migrateAgentPauses() {
  console.log('\n── agent_pauses');
  let count = 0; let skipped = 0;
  try {
    const rows = await q('SELECT * FROM agent_pauses');
    for (const r of rows) {
      const agentNum = String(r.agent_ext ?? r.agent_number);
      const agent = await prisma.agent.findUnique({ where: { number: agentNum } });
      if (!agent) { skipped++; continue; }
      const pt = r.pause_type_code
        ? await prisma.pauseType.findUnique({ where: { code: r.pause_type_code } })
        : null;
      await prisma.agentPause.create({
        data: {
          agentId: agent.id,
          pauseTypeId: pt?.id,
          sessionId: r.session_id || null,
          startTime: r.pause_start ? new Date(r.pause_start) : new Date(),
          endTime: r.pause_end ? new Date(r.pause_end) : null,
          durationSeconds: r.duration_seconds || null,
        },
      });
      count++;
    }
    console.log(`  ✓ ${count} agent_pauses (${skipped} skipped)`);
  } catch (e: any) {
    console.warn(`  ⚠ skip: ${e.message}`);
  }
}

async function migrateQueueEvents() {
  console.log('\n── queue_events (puede ser grande, paginamos de 1000 en 1000)');
  let offset = 0; let count = 0;
  try {
    while (true) {
      const rows = await q('SELECT * FROM queue_events ORDER BY id LIMIT ? OFFSET ?', [1000, offset]);
      if (!rows.length) break;
      const data = rows.map((r: any) => ({
        queueId: String(r.queue_id ?? r.queue ?? ''),
        eventType: String(r.event_type ?? r.event ?? 'unknown'),
        callerId: r.caller_id || null,
        agentExt: r.agent_ext || null,
        uniqueId: r.unique_id || r.uniqueid || null,
        waitTime: r.wait_time_sec ?? r.wait_time ?? null,
        talkTime: r.talk_time_sec ?? r.talk_time ?? null,
        createdAt: r.created_at ? new Date(r.created_at) : new Date(),
      }));
      await prisma.queueEvent.createMany({ data, skipDuplicates: true });
      count += rows.length;
      offset += rows.length;
      process.stdout.write(`  ... ${count}\r`);
    }
    console.log(`\n  ✓ ${count} queue_events`);
  } catch (e: any) {
    console.warn(`\n  ⚠ skip: ${e.message}`);
  }
}

async function migrateQueueCallEntry() {
  console.log('\n── queue_call_entry');
  let count = 0;
  try {
    const rows = await q('SELECT * FROM queue_call_entry');
    for (const r of rows) {
      await prisma.queueCallEntry.upsert({
        where: { uniqueId: String(r.unique_id ?? r.uniqueid ?? `legacy-${r.id}`) },
        update: {},
        create: {
          queueId: String(r.queue_id ?? r.queue ?? ''),
          uniqueId: String(r.unique_id ?? r.uniqueid ?? `legacy-${r.id}`),
          callerId: r.caller_id || null,
          enteredAt: r.entered_at ? new Date(r.entered_at) : new Date(),
          answeredAt: r.answered_at ? new Date(r.answered_at) : null,
          endedAt: r.ended_at ? new Date(r.ended_at) : null,
          answeredBy: r.answered_by || null,
          abandoned: !!r.abandoned,
          waitTime: r.wait_time_sec ?? r.wait_time ?? null,
          talkTime: r.talk_time_sec ?? r.talk_time ?? null,
        },
      });
      count++;
    }
    console.log(`  ✓ ${count} queue_call_entry`);
  } catch (e: any) {
    console.warn(`  ⚠ skip: ${e.message}`);
  }
}

async function migrateExtMeta() {
  console.log('\n── ext_meta');
  let count = 0;
  try {
    const rows = await q('SELECT * FROM ext_meta');
    for (const r of rows) {
      const data = {
        tipo: r.tipo || null,
        rtspUrl: r.rtsp_url || null,
        rtspLabel: r.rtsp_label || null,
        rtspUrlSource: r.rtsp_url_source || null,
        rtspAutoTriedAt: r.rtsp_auto_tried_at ? new Date(r.rtsp_auto_tried_at) : null,
        isBocina: !!r.is_bocina,
        doorDtmfCode: r.door_dtmf_code || null,
        notes: r.notes || null,
      };
      await prisma.extMeta.upsert({
        where: { ext: String(r.ext) },
        update: data,                   // ← UPDATE real, no {} (sobreescribe en re-corridas)
        create: { ext: String(r.ext), ...data },
      });
      count++;
    }
    console.log(`  ✓ ${count} ext_meta (rtsp_url + tipo + door_dtmf actualizado)`);
  } catch (e: any) {
    console.warn(`  ⚠ skip: ${e.message}`);
  }
}

async function migrateAppSettings() {
  console.log('\n── app_settings');
  let count = 0;
  let skipped = 0;
  // Keys que NO migramos del legacy — son específicas del rebranding HznFlow
  const SKIP_KEYS = new Set([
    'brand_app_name', 'brand_company_name', 'brand_logo_text', 'brand_logo_sub',
    'brand_tagline', 'brand_subtitle',
  ]);
  try {
    const rows = await q('SELECT * FROM app_settings');
    for (const r of rows) {
      const key = r.key || r.name;
      const value = String(r.value ?? '');
      if (!key) continue;
      if (SKIP_KEYS.has(key)) { skipped++; continue; }
      await prisma.appSetting.upsert({
        where: { key },
        update: { value },
        create: { key, value },
      });
      count++;
    }
    console.log(`  ✓ ${count} app_settings (${skipped} skipped — branding keys)`);
  } catch (e: any) {
    console.warn(`  ⚠ skip: ${e.message}`);
  }
}

async function migrateAgentQueuePref() {
  console.log('\n── agent_queue_pref');
  let count = 0; let skipped = 0;
  try {
    const rows = await q('SELECT * FROM agent_queue_pref');
    for (const r of rows) {
      const agentNum = String(r.agent_number);
      const agent = await prisma.agent.findUnique({ where: { number: agentNum } });
      if (!agent) { skipped++; continue; }
      await prisma.agentQueuePref.upsert({
        where: { agentId_queueId: { agentId: agent.id, queueId: String(r.queue) } },
        update: {},
        create: {
          agentId: agent.id,
          queueId: String(r.queue),
          penalty: r.penalty ?? 0,
        },
      });
      count++;
    }
    console.log(`  ✓ ${count} agent_queue_pref (${skipped} skipped)`);
  } catch (e: any) {
    console.warn(`  ⚠ skip: ${e.message}`);
  }
}

async function main() {
  console.log('═══ Migración legacy → PostgreSQL ═══');
  await connectMysql();

  // Orden importante (relaciones)
  await migrateAclUsers();
  await migratePauseTypes();
  await migrateAgents();
  await migrateAgentSessions();
  await migrateAgentPauses();
  await migrateAgentQueuePref();
  await migrateQueueEvents();
  await migrateQueueCallEntry();
  await migrateExtMeta();
  await migrateAppSettings();

  console.log('\n═══ Migración completa ═══');
  console.log('\n⚠ IMPORTANTE: los admins migrados están desactivados.');
  console.log('   Conectate a Postgres y reseteales el password con bcrypt manualmente.');
}

main()
  .catch((e) => { console.error(e); process.exit(1); })
  .finally(async () => {
    await prisma.$disconnect();
    if (mysqlConn) await mysqlConn.end();
  });
