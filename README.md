# Kontur AI — Laravel + Vue 3 платформа генерации контента

Демонстрационный продукт для вакансии **Senior Fullstack (PHP/Laravel/Vue3/TypeScript)**:
не CRUD-заготовка, а рабочий контур генерации изображений, видео, аудио и текста —
с очередями, AI-провайдерами за общим контрактом, кредитами, идемпотентностью,
лимитами и метриками.

> **Стек:** PHP 8.3+ · Laravel 12 · MySQL 8 / SQLite · Redis · Vue 3 (Composition API) ·
> TypeScript · Inertia.js 3 · SSR (Node) · Sanctum · Tailwind CSS 4 · PHPUnit · Larastan ·
> GitHub Actions · Docker

---

## Почему Laravel 12, а не 11

В вакансии указан Laravel 10–11. Проект собран на **Laravel 12**, потому что вся ветка 11.x
на момент сборки закрыта security advisories Packagist (в частности CRLF-инъекция в
`email`-правиле, `PKSA-mdq4-51ck-6kdq`) — Composer отказывается её устанавливать.
API 12 полностью совместим с 11: `bootstrap/app.php`, FormRequest, Eloquent, Sanctum,
очереди и ресурсы используются без изменений. Понижение до 11 — это правка `composer.json`
и повторная установка, код приложения менять не нужно.

---

## Что реализовано

### Backend

| Слой | Реализация |
|------|-----------|
| HTTP | `FormRequest` → `Controller` → `JsonResource` |
| Домен | `Service` + `DTO` (readonly) + `Enum` |
| Данные | `Repository` + Eloquent-скоупы, индексы под реальные запросы |
| Асинхронность | `Job` → `AiProvider` с ретраями, таймаутом и возвратом кредитов |
| Кеш | агрегаты дашборда и статистики проектов, инвалидация на мутациях |
| Лимиты | отдельные rate limiter'ы для auth / генераций / API |

**Ключевые решения:**

- **Один контракт — четыре модальности.** `App\Contracts\AiProvider` (`name`, `modelFor`,
  `generate`). `StubProvider` генерирует валидные PNG/WAV/текст локально (GD), поэтому весь
  пайплайн работает без ключей и в CI. `OpenAiCompatibleProvider` ходит в любой
  OpenAI-совместимый шлюз (OpenAI, OpenRouter, YandexGPT-совместимые).
- **Кредиты списываются при создании, возвращаются при провале.** Списание и запись
  генерации — одна транзакция. Возврат — в `GenerationService::fail()`/`cancel()`, то есть
  ровно один раз, вне зависимости от числа ретраев.
- **Идемпотентность.** Уникальный индекс `(user_id, idempotency_key)`; повторный POST
  возвращает существующую генерацию и не списывает кредиты второй раз.
- **Лимит параллельных задач зависит от тарифа** (`Plan::concurrentGenerations()`),
  проверяется до списания кредитов.
- **Usage-логи пишутся только по факту списания**, а счётчики генераций берутся из
  `generations` — поэтому «всего» всегда равно `completed + failed + cancelled`.
- **Ассеты приватны.** Файлы лежат на диске вне `public/`, отдаются контроллером с проверкой
  владельца (`/api/v1/assets/{uuid}/download` для токена, `/media/{uuid}` для сессии).

### Frontend

- **Vue 3 Composition API + TypeScript** (strict, `vue-tsc` в CI), Inertia 3, Tailwind CSS 4.
- **SSR на Node.** `resources/js/ssr.ts` + `@inertiajs/vite`; клиент гидрирует готовый HTML
  (`createSSRApp`). Проверено: лендинг отдаётся ~18 КБ разметки против ~3 КБ без SSR.
- Страницы: лендинг, вход/регистрация, дашборд, студия, история, медиатека, проекты, usage.
- **Поллинг статуса** вынесен в `useGenerationPolling` — останавливается на финальном
  статусе, при исчерпании попыток и при размонтировании компонента.
- Доступность: `aria-*` на переключателях и полях, видимый `:focus-visible`,
  `prefers-reduced-motion` отключает анимации.

### Инфраструктура

- **Docker:** multi-stage `Dockerfile` (frontend → composer → php-fpm → nginx → ssr),
  `docker-compose.yml` с `app`, `nginx`, `mysql`, `redis`, **отдельными** `queue` и
  `scheduler`, `ssr`. Healthcheck'и на MySQL/Redis/SSR.
- **CI:** Pint, Larastan level 6, `vue-tsc`, сборка бандлов, тесты на PHP 8.3 и 8.4,
  проверка сборки Docker-образа.
- **Makefile:** `install`, `dev`, `test`, `lint`, `analyse`, `ci`, `up`, `fresh`, `ssr`.

---

## Быстрый старт (локально)

Нужны PHP 8.3+, Composer, Node 22+.

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate --seed     # SQLite, демо-данные
npm run build                  # клиентский + SSR бандлы
```

Три процесса:

```bash
php artisan serve                      # http://127.0.0.1:8000
php artisan queue:work --queue=generations,default
php artisan inertia:start-ssr          # SSR на :13714
```

Демо-аккаунт: `demo@kontur.ai` / `demo-password-2026` (тариф Pro, 7450 кредитов,
30 генераций за две недели).

> На Windows без mbstring в глобальном `php.ini` используйте локальный конфиг:
> `php -c /path/to/php.ini artisan serve`.

### Docker

```bash
cp .env.docker.example .env.docker
# APP_KEY=base64:... → php artisan key:generate --show
docker compose up -d --build
docker compose exec app php artisan migrate --seed --force
```

Приложение — `http://localhost:8080`, SSR — `:13714`.

---

## Тесты и качество

```bash
php artisan test                 # Feature + Unit
./vendor/bin/pint --test         # стиль
./vendor/bin/phpstan analyse     # Larastan level 6
npm run typecheck                # vue-tsc
```

Покрыто: аутентификация (Sanctum + сессии), CRUD проектов с изоляцией по владельцу,
создание генераций (кредиты, идемпотентность, лимиты, rate limit), полный happy-path
через очередь, отказ провайдера с возвратом кредитов, отмена, отдача ассетов,
агрегаты usage, Inertia-страницы и их пропсы, детерминизм stub-провайдера, enum'ы и DTO.

---

## Архитектура

```
app/
├── Contracts/AiProvider.php          # контракт провайдера
├── DTO/                              # readonly-объекты на границах
├── Enums/                            # типы, статусы, тарифы
├── Exceptions/                       # доменные исключения → HTTP-коды
├── Http/
│   ├── Controllers/Api/V1/           # REST API (Sanctum)
│   ├── Controllers/                  # Inertia-страницы (сессия)
│   ├── Middleware/                   # Inertia share, security headers
│   ├── Requests/Api/V1/              # валидация
│   └── Resources/                    # сериализация ответов
├── Jobs/ProcessGeneration.php        # единственная точка вызова провайдера
├── Models/                           # Eloquent + PHPDoc-свойства
├── Repositories/                     # запросы, агрегаты, индексы
└── Services/                         # бизнес-логика
    └── Ai/Providers/                 # Stub, OpenAI-совместимый
```

Поток создания генерации:

```
POST /api/v1/projects/{id}/generations
  → StoreGenerationRequest         валидация
  → GenerationService::create()    проверка лимитов, транзакция, списание кредитов
  → ProcessGeneration (queue)      ретраи 3× с backoff [10, 60, 180]
  → AiProvider::generate()         вызов вендора
  → AssetService::storeProviderResult()   запись файлов + checksum
  → GenerationService::complete()  статус, usage_log, инвалидация кеша
```

При ошибке — `fail()`: статус `failed`, кредиты возвращены, `usage_log` не пишется.

---

## API

Полная спецификация — [`docs/openapi.yaml`](docs/openapi.yaml) (OpenAPI 3.1, 13 путей).

| Метод | Путь | Назначение |
|-------|------|-----------|
| POST | `/api/v1/auth/register` | регистрация + токен |
| POST | `/api/v1/auth/login` | вход + токен |
| GET | `/api/v1/auth/me` | профиль |
| GET/POST | `/api/v1/projects` | список / создание |
| GET/PUT/DELETE | `/api/v1/projects/{id}` | чтение / обновление / soft delete |
| POST | `/api/v1/projects/{id}/generations` | создать генерацию (`202`) |
| GET | `/api/v1/generations` | история с фильтрами |
| GET | `/api/v1/generations/{uuid}` | статус и ассеты |
| POST | `/api/v1/generations/{uuid}/cancel` | отмена с возвратом кредитов |
| GET | `/api/v1/assets` | медиатека |
| GET | `/api/v1/assets/{uuid}/download` | файл (только владельцу) |
| GET | `/api/v1/usage` | агрегаты за период |

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"email":"demo@kontur.ai","password":"demo-password-2026"}'

curl -X POST http://localhost:8000/api/v1/projects/1/generations \
  -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"type":"image","prompt":"неоновый город под дождём","idempotency_key":"draft-42"}'
```

Коды ошибок: `401 unauthenticated`, `402 insufficient_credits`, `403 forbidden`,
`404 not_found`, `429 rate_limited` / `concurrency_limit`, `422 validation_error`.

---

## Подключение реального AI

```dotenv
AI_PROVIDER=openai
OPENAI_API_KEY=sk-...
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_IMAGE_MODEL=gpt-image-1
OPENAI_TEXT_MODEL=gpt-4o-mini
```

Новый вендор = новый класс, реализующий `AiProvider`, плюс ветка в `AiProviderManager`.
Сервисы, джоб и контроллеры не меняются.

---

## Что дальше

- WebSocket/SSE вместо поллинга (Laravel Reverb) — статус генерации в реальном времени.
- Биллинг: Stripe/ЮKassa, пополнение кредитов, инвойсы.
- Модерация промптов и результатов до постановки в очередь.
- S3 + CDN для ассетов (`ASSETS_DISK=s3` уже поддержан конфигом).
- Горизонтальное масштабирование воркеров с приоритетными очередями по тарифам.