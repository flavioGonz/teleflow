# ─── Builder stage ───────────────────────────────────────────────
FROM node:22-alpine AS builder
WORKDIR /app

# Instalar deps con cache layer
COPY package.json package-lock.json* ./
RUN npm ci

# Generar Prisma client + compilar TS
COPY prisma ./prisma
RUN npx prisma generate

COPY tsconfig.json ./
COPY src ./src
RUN npx tsc

# ─── Runtime stage ───────────────────────────────────────────────
FROM node:22-alpine
WORKDIR /app
ENV NODE_ENV=production

# wget para healthcheck
RUN apk add --no-cache wget

# Solo runtime deps
COPY package.json package-lock.json* ./
RUN npm ci --omit=dev && npm cache clean --force

# Prisma client + esquema (necesario para migrate deploy en startup)
COPY --from=builder /app/node_modules/.prisma ./node_modules/.prisma
COPY --from=builder /app/node_modules/@prisma ./node_modules/@prisma
COPY prisma ./prisma

# Build compilado
COPY --from=builder /app/dist ./dist

EXPOSE 4000

# Aplica migraciones de Prisma + arranca server.
# En prod: si existen archivos de migración (prisma/migrations/), usa `migrate deploy`.
# Si no existen, fallback a `db push` (útil en dev/staging temprano).
CMD sh -c "if [ -d prisma/migrations ]; then npx prisma migrate deploy; else npx prisma db push --skip-generate --accept-data-loss; fi && node dist/index.js"
