# Makefile pro PHP projekt běžící v Dockeru.
#
# Ukázka k dokumentu ../README.md. Příkazy jsou skutečné — vyzkoušet
# se dají naprázdno přes `make -n <cíl>`, který vypíše, co by se spustilo.

# --- Nastavení -------------------------------------------------------------

# Uživatel a skupina, pod kterými poběží příkazy v kontejneru.
# Bez tohohle vytvoří kontejner soubory vlastněné rootem a lokálně
# je pak nejde smazat ani upravit.
UID := $(shell id -u)
GID := $(shell id -g)

# Compose v2 se volá `docker compose` (bez pomlčky).
DOCKER_COMPOSE := docker compose

# exec = běžící kontejner, rychlejší
# run --rm = nový kontejner, funguje i když projekt neběží
PHP  := $(DOCKER_COMPOSE) exec --user $(UID):$(GID) php
PHP_RUN := $(DOCKER_COMPOSE) run --rm --user $(UID):$(GID) php

# -T vypne TTY. V CI žádné terminálové zařízení není a bez toho
# příkaz spadne na „the input device is not a TTY".
ifdef CI
	PHP := $(DOCKER_COMPOSE) exec -T --user $(UID):$(GID) php
endif

# Bez tohohle by `make` bez argumentu spustil první cíl v souboru.
.DEFAULT_GOAL := help

# --- Nápověda --------------------------------------------------------------

.PHONY: help
help: ## Vypíše tenhle přehled
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| LC_ALL=C sort \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

# --- Prostředí -------------------------------------------------------------

.PHONY: init
init: ## První spuštění projektu — od nuly po běžící aplikaci
	cp -n .env.example .env || true
	$(DOCKER_COMPOSE) build
	$(DOCKER_COMPOSE) up -d
	$(MAKE) composer-install
	$(MAKE) db-migrate

.PHONY: up
up: ## Nastartuje kontejnery
	$(DOCKER_COMPOSE) up -d

.PHONY: down
down: ## Zastaví kontejnery
	$(DOCKER_COMPOSE) down

.PHONY: restart
restart: down up ## Restartuje kontejnery

.PHONY: logs
logs: ## Sleduje logy (Ctrl+C ukončí)
	$(DOCKER_COMPOSE) logs -f

.PHONY: sh
sh: ## Otevře shell v PHP kontejneru
	$(PHP) sh

# --- Composer --------------------------------------------------------------

.PHONY: composer-install
composer-install: ## Nainstaluje závislosti
	$(PHP) composer install

.PHONY: composer-update
composer-update: ## Aktualizuje závislosti
	$(PHP) composer update

.PHONY: composer
composer: ## Libovolný composer příkaz: make composer CMD="require foo/bar"
	$(PHP) composer $(CMD)

# --- Testy a kontroly ------------------------------------------------------

.PHONY: test
test: ## Spustí testy; lze zúžit: make test ARGS="--filter=OrderTest"
	$(PHP) vendor/bin/phpunit $(ARGS)

.PHONY: test-coverage
test-coverage: ## Testy s pokrytím do var/coverage
	$(PHP) vendor/bin/phpunit --coverage-html var/coverage

.PHONY: stan
stan: ## Statická analýza (PHPStan)
	$(PHP) vendor/bin/phpstan analyse

.PHONY: cs
cs: ## Zkontroluje styl kódu, nic nemění
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

.PHONY: cs-fix
cs-fix: ## Opraví styl kódu
	$(PHP) vendor/bin/php-cs-fixer fix

.PHONY: check
check: cs stan test ## Vše, co má projít před odesláním k review

# --- Databáze --------------------------------------------------------------

.PHONY: db-migrate
db-migrate: ## Spustí migrace
	$(PHP) bin/console doctrine:migrations:migrate --no-interaction

.PHONY: db-reset
db-reset: ## Smaže databázi a postaví ji znovu i s fixturami
	$(PHP) bin/console doctrine:database:drop --force --if-exists
	$(PHP) bin/console doctrine:database:create
	$(MAKE) db-migrate
	$(PHP) bin/console doctrine:fixtures:load --no-interaction

# --- CI --------------------------------------------------------------------

.PHONY: ci
ci: ## Totéž co spouští pipeline — spustitelné i lokálně
	$(MAKE) cs
	$(MAKE) stan
	$(MAKE) test
