# Импорт/Экспорт товаров

Тестовое задание: асинхронный импорт товаров из `.xlsx`, скачивание изображений, REST API.

## Стек

- **Backend**: PHP 8.2, Slim 4, Doctrine ORM 3, Symfony Messenger (RabbitMQ), PhpSpreadsheet, Guzzle.
- **БД**: PostgreSQL 18.
- **Очередь**: RabbitMQ 3.13.
- **Инфраструктура**: Docker, nginx.

## Быстрый старт

```bash
# 1. Скопировать .env
cp .env.example .env

# 2. Поднять всё одной командой
make up
# или
./scripts/start.sh
