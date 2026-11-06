.PHONY: help first-up up down restart install migrate status consume-import consume-images test phpstan openapi shell logs

PHP_CLI = sherparpa-php-cli

help:
	@echo "Available commands:"
	@echo "  make first-up         - First run: .env, build, up, composer install, migrate, status"
	@echo "  make up               - Start containers (no build, no install)"
	@echo "  make down             - Stop and remove containers"
	@echo "  make restart          - Restart containers"
	@echo "  make install          - Install composer dependencies"
	@echo "  make migrate          - Run database migrations"
	@echo "  make status           - Show worker jobs status"
	@echo "  make consume-import   - Run import worker (foreground)"
	@echo "  make consume-images   - Run images worker (foreground)"
	@echo "  make test             - Run PHPUnit tests"
	@echo "  make phpstan          - Run PHPStan analysis"
	@echo "  make openapi          - Generate openapi.yaml"
	@echo "  make shell            - Open shell in php-cli container"
	@echo "  make logs             - Tail all container logs"

first-up:
	./scripts/start.sh

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart

install:
	docker compose run --rm $(PHP_CLI) composer install --no-interaction --prefer-dist

migrate:
	docker compose run --rm $(PHP_CLI) php /app/backend/bin/console migrate --no-interaction

status:
	docker compose run --rm $(PHP_CLI) php /app/backend/bin/console app:status

consume-import:
	docker compose run --rm $(PHP_CLI) php /app/backend/bin/console app:consume import

consume-images:
	docker compose run --rm $(PHP_CLI) php /app/backend/bin/console app:consume images

test:
	docker compose run --rm $(PHP_CLI) composer --working-dir=/app/backend test

phpstan:
	docker compose run --rm $(PHP_CLI) composer --working-dir=/app/backend phpstan

openapi:
	docker compose run --rm $(PHP_CLI) composer --working-dir=/app/backend openapi

shell:
	docker compose run --rm $(PHP_CLI) sh

logs:
	docker compose logs -f
