/**
 * Conexión al MySQL remoto de Asterisk.
 * Dos pools separados — uno para la DB de config (`asterisk`)
 * y otro para CDR (`asteriskcdrdb`).
 *
 * Graceful degradation: si el host no responde, las queries tiran
 * un error tipado que los routes pueden mapear a 503 + warning.
 */
import mysql, { Pool } from 'mysql2/promise';
import { env } from '../lib/env.js';

export class AsteriskUnavailableError extends Error {
  status = 503;
  constructor(msg: string) {
    super(`Asterisk MySQL no disponible: ${msg}`);
    this.name = 'AsteriskUnavailableError';
  }
}

function makePool(database: string): Pool {
  return mysql.createPool({
    host: env.ASTERISK_DB_HOST,
    port: env.ASTERISK_DB_PORT,
    user: env.ASTERISK_DB_USER,
    password: env.ASTERISK_DB_PASS,
    database,
    waitForConnections: true,
    connectionLimit: 5,
    queueLimit: 0,
    charset: 'utf8mb4',
    connectTimeout: 3000,
  });
}

export const asteriskDb = makePool(env.ASTERISK_DB_NAME);
export const cdrDb = makePool(env.CDR_DB_NAME);

function isConnError(e: any): boolean {
  return e && (
    e.code === 'ECONNREFUSED' ||
    e.code === 'ETIMEDOUT' ||
    e.code === 'ENOTFOUND' ||
    e.code === 'EHOSTUNREACH' ||
    e.code === 'PROTOCOL_CONNECTION_LOST'
  );
}

/** Helper: query a la DB asterisk con degradation */
export async function aq<T = any>(sql: string, params: any[] = []): Promise<T[]> {
  try {
    const [rows] = await asteriskDb.query(sql, params);
    return rows as T[];
  } catch (e: any) {
    if (isConnError(e)) throw new AsteriskUnavailableError(e.code);
    throw e;
  }
}

/** Helper: query a la DB CDR con degradation */
export async function cdrq<T = any>(sql: string, params: any[] = []): Promise<T[]> {
  try {
    const [rows] = await cdrDb.query(sql, params);
    return rows as T[];
  } catch (e: any) {
    if (isConnError(e)) throw new AsteriskUnavailableError(e.code);
    throw e;
  }
}
