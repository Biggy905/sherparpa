# Импорт/Экспорт товаров

Тестовое задание: backend-приложение для асинхронного импорта товаров из `.xlsx` с парсингом атрибутов и фоновым скачиванием изображений.

## Стек

- **PHP 8.2** + **Slim 4**
- **Doctrine ORM 3** + **PostgreSQL**
- **Symfony Messenger** + **RabbitMQ**
- **PhpSpreadsheet** + **Guzzle**
- **PHP-DI**, **Monolog**
- **PHPStan (level 9)**, **PHPUnit**, **PHP CS Fixer**

## Запуск проекта

Проект полностью контейнеризирован. Вам потребуются только Docker и Docker Compose.

1. Склонируйте репозиторий и перейдите в папку проекта.
2. Запустите скрипт первого старта:

```bash
make first-up
```
### Скрипт автоматически:

- создаст .env из .env.example;

- создаст внешнюю сеть sherparpa-network;

- соберет и запустит контейнеры;

- дождется готовности PostgreSQL и RabbitMQ;

- установит Composer-зависимости;

- выполнит миграции базы данных;

- запустит воркеры (import и images).

### Доступные сервисы после запуска:

- API: http://localhost:3000

- Frontend: http://localhost:3010

- Swagger UI: http://localhost:8081

- RabbitMQ Management: http://localhost:15672 (guest/guest)


## Архитектура
### Проект разделен на слои (SOLID):

- Controllers — валидация запроса и возврат JSON-ответа.

- Services — бизнес-логика (парсинг, оркестрация импорта).

- Repositories — запросы к БД через Doctrine.

- Entities — доменные сущности (Product, ProductAttribute, ProductImage, WorkerJob).

- Groups — формирование JSON-ответов (без тяжелых DTO).

- Handlers — обработка асинхронных сообщений воркерами.

### Этапы импорта
1) Загрузка: POST /api/imports сохраняет файл, создает задачу WorkerJob (pending) и отправляет сообщение в очередь import.

2) Товары: воркер import парсит Excel и в транзакции сохраняет товары, атрибуты и изображения (upsert по external_code). После коммита на каждое изображение отправляется сообщение в очередь images.

3) Изображения: воркер images забирает сообщения, скачивает файлы через Guzzle и сохраняет пути в БД.

4) Мониторинг: фронтенд опрашивает GET /api/imports/{job_id} и показывает прогресс обоих этапов.

## API-контракт (основное)
### GET /api/health
Проверка статуса БД и RabbitMQ.

### POST /api/imports
Загрузка .xlsx файла (multipart/form-data, поле file).
Ответ 202: {"job_id": "uuid", "status": "pending"}

### GET /api/imports/{job_id}
Статус импорта с прогрессом по товарам и изображениям.

### GET /api/products?page=1&limit=20&name=&price_from=&price_to=
Список товаров с серверной пагинацией и фильтрацией.

### GET /api/products/{id}
Карточка товара с атрибутами и изображениями.

### Консольные команды
`make status` — диагностика очередей (выводит статусы из БД).

`make migrate` — применение миграций.

`make consume-import` — запуск воркера импорта в foreground.

`make consume-images` — запуск воркера картинок в foreground.

`make openapi` — генерация openapi.yaml.

`make shell` — зайти внутрь php-cli контейнера.

### Тестирование и качество кода
`make test` — запуск PHPUnit (Unit, Integration, E2E).

`make phpstan` — статический анализ (level 9).

`make cs:check` — проверка стиля кода (PHP CS Fixer).

### Переменные окружения
Все настройки хранятся в .env (создается автоматически из .env.example).
Основные параметры:

`DB_*` — доступы к PostgreSQL.

`RABBITMQ_DSN` — строка подключения к RabbitMQ.

`APP_DEBUG` — режим отладки (включен по умолчанию).
___
Backend и frontend реализован в рамках тестового задания.