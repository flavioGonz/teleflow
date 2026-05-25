import 'dotenv/config';

function req(name: string): string {
  const v = process.env[name];
  if (!v) throw new Error(`Missing env var ${name}`);
  return v;
}

export const env = {
  PORT: parseInt(process.env.PORT || '4000', 10),
  NODE_ENV: process.env.NODE_ENV || 'development',
  CORS_ORIGIN: process.env.CORS_ORIGIN || 'http://localhost:3000',

  JWT_SECRET: req('JWT_SECRET'),
  JWT_EXPIRES_IN: process.env.JWT_EXPIRES_IN || '24h',

  ASTERISK_DB_HOST: req('ASTERISK_DB_HOST'),
  ASTERISK_DB_PORT: parseInt(process.env.ASTERISK_DB_PORT || '3306', 10),
  ASTERISK_DB_USER: req('ASTERISK_DB_USER'),
  ASTERISK_DB_PASS: req('ASTERISK_DB_PASS'),
  ASTERISK_DB_NAME: process.env.ASTERISK_DB_NAME || 'asterisk',
  CDR_DB_NAME: process.env.CDR_DB_NAME || 'asteriskcdrdb',

  AMI_HOST: req('AMI_HOST'),
  AMI_PORT: parseInt(process.env.AMI_PORT || '5038', 10),
  AMI_USER: req('AMI_USER'),
  AMI_PASS: req('AMI_PASS'),
};
