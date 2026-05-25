# HznFlow v2 — comandos de desarrollo.
# Uso: `make` (sin args) muestra ayuda. `make dev` levanta todo.

.PHONY: help dev prod down logs migrate-from-mysql clean reset seed shell-backend shell-db

# Auto-crea .env desde .env.example si no existe
ENV_FILE := .env
$(ENV_FILE):
	@if [ ! -f $(ENV_FILE) ]; then \
		cp .env.example $(ENV_FILE); \
		echo "✓ .env creado desde .env.example — editalo si necesitás conectar a Asterisk real"; \
	fi

help: ## Lista de comandos disponibles
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-22s\033[0m %s\n", $$1, $$2}'

dev: $(ENV_FILE) ## Levanta stack completo con hot-reload (postgres + backend + frontend)
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up --build

dev-d: $(ENV_FILE) ## Igual que dev pero detached (background)
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up --build -d
	@echo "✓ stack arriba"
	@echo "  Frontend: http://localhost:3000"
	@echo "  Backend:  http://localhost:4000/api/health"
	@echo "  Logs:     make logs"

prod-local: $(ENV_FILE) ## Smoke test del build de prod en local (usa Postgres dockerizado)
	docker compose up --build -d
	@echo "✓ build de prod corriendo localmente — http://localhost:3000"

prod-deploy: ## Deploy real de producción (requiere .env.prod completo)
	@test -f .env.prod || (echo "✗ Falta .env.prod — copiá de .env.prod.example"; exit 1)
	@test -n "$$(grep '^JWT_SECRET=.\+' .env.prod)" || (echo "✗ JWT_SECRET vacío en .env.prod"; exit 1)
	@test -n "$$(grep '^DATABASE_URL=.\+' .env.prod)" || (echo "✗ DATABASE_URL vacío en .env.prod"; exit 1)
	docker compose --env-file .env.prod -f docker-compose.yml -f docker-compose.prod.yml up -d --build
	@echo "✓ deploy completado"

prod-logs: ## Logs de producción
	docker compose --env-file .env.prod -f docker-compose.yml -f docker-compose.prod.yml logs -f --tail=200

prod-down: ## Detener producción
	docker compose --env-file .env.prod -f docker-compose.yml -f docker-compose.prod.yml down

prod-migrate: ## Aplicar migraciones Prisma en prod (DESTRUCTIVO — usar con cuidado)
	docker compose --env-file .env.prod -f docker-compose.yml -f docker-compose.prod.yml exec backend npx prisma migrate deploy

down: ## Detiene todos los containers
	docker compose -f docker-compose.yml -f docker-compose.dev.yml down

logs: ## Tail de logs
	docker compose logs -f --tail=100

logs-backend: ## Solo logs del backend
	docker compose logs -f --tail=100 backend

migrate-from-mysql: $(ENV_FILE) ## Importa data del MySQL legacy a Postgres (requiere VPN/red Horizon)
	docker compose exec backend npx tsx prisma/migrate-from-legacy.ts

seed: ## Re-corre el seed manual
	docker compose exec backend npx tsx prisma/seed.ts

shell-backend: ## Shell dentro del container backend
	docker compose exec backend sh

shell-db: ## psql interactivo en la DB Postgres
	docker compose exec postgres psql -U hznflow -d hznflow

reset: ## Destructivo: borra DB + volúmenes y rearranca limpio
	@echo "⚠ Esto borra TODA la data local de Postgres."
	@read -p "¿Seguro? (y/N) " ok && [ "$$ok" = "y" ]
	docker compose -f docker-compose.yml -f docker-compose.dev.yml down -v
	$(MAKE) dev-d

clean: ## Detiene + borra imágenes builddeadas
	docker compose -f docker-compose.yml -f docker-compose.dev.yml down --rmi local

.DEFAULT_GOAL := help
