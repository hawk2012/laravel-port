# Kontur AI — команды разработки и деплоя.
# Все цели работают из корня проекта.

.DEFAULT_GOAL := help
.PHONY: help install dev build test lint analyse ci up down logs shell migrate fresh seed queue ssr clean

PHP := php
COMPOSER := $(PHP) ../composer.phar
DC := docker compose

help: ## Показать список команд
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

install: ## Установить зависимости, собрать фронт, подготовить БД
	$(COMPOSER) install
	npm ci
	cp -n .env.example .env || true
	$(PHP) artisan key:generate
	$(PHP) artisan migrate --seed
	npm run build

dev: ## Локальная разработка: vite + сервер + очередь
	npm run dev & $(PHP) artisan serve & $(PHP) artisan queue:work --queue=generations,default

build: ## Собрать клиентский и SSR-бандлы
	npm run build

test: ## Прогнать тесты
	$(PHP) artisan config:clear
	$(PHP) artisan test

lint: ## PHP-стиль (Pint) + проверка типов TS
	./vendor/bin/pint --test
	npm run typecheck

analyse: ## Статический анализ (Larastan level 6)
	./vendor/bin/phpstan analyse --memory-limit=1G

ci: lint analyse test ## Полная проверка как в CI

up: ## Поднять Docker-стек
	$(DC) up -d --build

down: ## Остановить стек
	$(DC) down

logs: ## Логи всех сервисов
	$(DC) logs -f --tail=100

shell: ## Shell внутри контейнера app
	$(DC) exec app sh

migrate: ## Миграции в контейнере
	$(DC) exec app php artisan migrate --force

fresh: ## Пересоздать БД с демо-данными
	$(DC) exec app php artisan migrate:fresh --seed --force

seed: ## Только сиды
	$(DC) exec app php artisan db:seed --force

queue: ## Запустить воркер локально
	$(PHP) artisan queue:work --queue=generations,default --tries=3

ssr: ## Запустить SSR-сервер локально
	$(PHP) artisan inertia:start-ssr

clean: ## Очистить кеши и собранные бандлы
	$(PHP) artisan optimize:clear
	rm -rf public/build bootstrap/ssr
