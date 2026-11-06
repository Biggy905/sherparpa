#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

PHP_CLI="sherparpa-php-cli"

# 1. .env
if [ ! -f .env ]; then
    echo "==> Creating .env from .env.example"
    cp .env.example .env
fi

# 2. External network
if ! docker network inspect sherparpa-network >/dev/null 2>&1; then
    echo "==> Creating external network: sherparpa-network"
    docker network create sherparpa-network
fi

# 3. Build & up
echo "==> Building and starting containers..."
docker compose up -d --build

# 4. Wait for PostgreSQL (via php-cli run)
echo "==> Waiting for PostgreSQL..."
for i in $(seq 1 30); do
    if docker compose run --rm -T "$PHP_CLI" php -r '
        $c = @fsockopen("sherparpa-postgres", 5432, $e, $s, 2);
        if ($c === false) { exit(1); }
        fclose($c);
    ' >/dev/null 2>&1; then
        echo "    PostgreSQL is ready."
        break
    fi
    if [ "$i" -eq 30 ]; then
        echo "    ERROR: PostgreSQL did not become ready in time." >&2
        exit 1
    fi
    sleep 1
done

# 5. Wait for RabbitMQ
echo "==> Waiting for RabbitMQ..."
for i in $(seq 1 30); do
    if docker compose run --rm -T "$PHP_CLI" php -r '
        $c = @fsockopen("sherparpa-rabbitmq", 5672, $e, $s, 2);
        if ($c === false) { exit(1); }
        fclose($c);
    ' >/dev/null 2>&1; then
        echo "    RabbitMQ is ready."
        break
    fi
    if [ "$i" -eq 30 ]; then
        echo "    ERROR: RabbitMQ did not become ready in time." >&2
        exit 1
    fi
    sleep 1
done

# 6. Composer
echo "==> Installing composer dependencies..."
docker compose run --rm -T "$PHP_CLI" composer install --working-dir=/app/backend --no-interaction --prefer-dist

# 7. Migrations
echo "==> Running migrations..."
docker compose run --rm -T "$PHP_CLI" php /app/backend/bin/console migrate --no-interaction

# 8. Status
echo "==> Worker status:"
docker compose run --rm -T "$PHP_CLI" php /app/backend/bin/console app:status || true

# 9. Done
echo ""
echo "==> Ready:"
echo "    API:       http://localhost:3000"
echo "    Swagger:   http://localhost:8081"
echo "    RabbitMQ:  http://localhost:15672 (guest/guest)"
echo ""
echo "==> Workers are running in containers:"
echo "    sherparpa-worker-import"
echo "    sherparpa-worker-images"
