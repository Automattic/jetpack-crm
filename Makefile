.DEFAULT_GOAL := help
SHELL := /bin/bash

PLUGIN_NAME := zero-bs-crm
# Use the locally-installed @wordpress/env (a devDependency) rather than fetching
# it via `npx --yes` on every call, which needs registry access on each run.
# Run `make install` (npm install) once to populate it.
WP_ENV_BIN := node_modules/.bin/wp-env
WP_ENV := COMPOSE_PROJECT_NAME=$(PLUGIN_NAME) $(WP_ENV_BIN)

# Guard: any wp-env target depends on this. If the binary is missing the recipe
# below runs and exits with a helpful message; if it exists, it's up to date and
# the recipe is skipped.
$(WP_ENV_BIN):
	@echo "Error: wp-env is not installed. Run 'make install' first." >&2
	@exit 1

## Development environment
install: ## Install PHP (Composer) and JS (npm) dependencies
	composer install
	npm install

install-hooks: ## Install git hooks (fail-fast guard against direct pushes to trunk)
	git config core.hooksPath .githooks
	@echo "Git hooks installed (core.hooksPath = .githooks)."

up: $(WP_ENV_BIN) ## Start WordPress in Docker (http://localhost:8888, admin/password)
	$(WP_ENV) start

down: $(WP_ENV_BIN) ## Stop the WordPress containers
	$(WP_ENV) stop

destroy: $(WP_ENV_BIN) ## Remove the WordPress containers and database
	$(WP_ENV) destroy

logs: $(WP_ENV_BIN) ## Tail the WordPress container logs
	$(WP_ENV) logs

cli: $(WP_ENV_BIN) ## Open a shell inside the cli container
	$(WP_ENV) run cli bash

wp: $(WP_ENV_BIN) ## Run an arbitrary wp-cli command, e.g. `make wp CMD="plugin list"`
	$(WP_ENV) run cli wp $(CMD)

## Test
# The suite needs the WordPress test library, which only exists inside the
# wp-env tests container (at /wordpress-phpunit). Running it on the host fails
# with "Failed to automatically locate WordPress or wordpress-develop".
# WORDPRESS_DEVELOP_DIR has to be set explicitly: the container's own WP_TESTS_DIR
# is not the variable tests/php/bootstrap.php looks at.
test: $(WP_ENV_BIN) ## Run the PHPUnit unit suite. Usage: make test [ARGS="--filter Foo"]
	$(WP_ENV) run tests-cli --env-cwd=wp-content/plugins/$(notdir $(CURDIR)) \
		bash -c 'WORDPRESS_DEVELOP_DIR=/wordpress-phpunit composer phpunit -- $(ARGS)'

test-acceptance: ## Run the Codeception acceptance suite (needs a configured WP + browser)
	composer tests

## Lint
lint: ## Run PHP CodeSniffer on changed files (vs trunk)
	composer cs

lint-staged: ## Run PHP CodeSniffer on staged files
	composer cs-staged

lint-css: ## Check WPDS design token usage in the Sass sources
	npm run lint:css

## Release
release: ## Prepare a release PR. Usage: make release VERSION=x.y.z
	@test -n "$(VERSION)" || { echo "Usage: make release VERSION=x.y.z"; exit 1; }
	node scripts/prepare-release.mjs $(VERSION)

build: ## Build dist/zero-bs-crm.zip (tracked source from HEAD; assets compiled fresh)
	./scripts/build-plugin.sh

clean: ## Remove the dist/ release staging directory
	rm -rf dist

i18n: ## Regenerate translation files (no-op: JPCRM translations come from translate.wordpress.org)
	@echo "No POT generation step for Jetpack CRM."

## Help
help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-16s\033[0m %s\n", $$1, $$2}'

.PHONY: install install-hooks up down destroy logs cli wp test test-acceptance lint lint-staged lint-css release build clean i18n help
