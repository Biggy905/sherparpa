#!/bin/bash
set -e

# Создаём тестовую БД, если она ещё не существует
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE DATABASE import_export_test;
    GRANT ALL PRIVILEGES ON DATABASE import_export_test TO $POSTGRES_USER;
EOSQL
