.DEFAULT_GOAL := help
SHELL := /bin/bash

PLUGIN_NAME := zero-bs-crm

# Where this checkout is, and where the main one is. They differ when make is run
# from a git worktree. wp-env only ever mounts the main checkout, but a worktree
# under .claude/worktrees/ sits inside that mount, so the container can see its
# files. What it can't see is vendor/ and jetpack_vendor/, which are gitignored
# and so absent from a fresh worktree: `make worktree-link` puts them there.
WORKTREE_ROOT := $(shell git rev-parse --show-toplevel 2>/dev/null)
MAIN_ROOT := $(patsubst %/.git,%,$(abspath $(shell git rev-parse --git-common-dir 2>/dev/null)))

# Empty in the main checkout; the worktree's path below it otherwise. Stays
# absolute (leading slash) when the worktree lives outside the main checkout,
# where the container cannot reach it at all. Distinguishing that case from the
# main checkout matters: conflating them runs the main checkout's tests and
# reports them as the worktree's.
WORKTREE_REL := $(if $(filter $(MAIN_ROOT),$(WORKTREE_ROOT)),,$(patsubst $(MAIN_ROOT)/%,%,$(WORKTREE_ROOT)))

# This checkout's path inside the container.
ENV_CWD := wp-content/plugins/$(notdir $(MAIN_ROOT))$(if $(WORKTREE_REL),/$(WORKTREE_REL))

# Use the locally-installed @wordpress/env (a devDependency) rather than fetching
# it via `npx --yes` on every call, which needs registry access on each run.
# Run `make install` (npm install) once to populate it. Always the main
# checkout's copy: worktrees have no node_modules.
WP_ENV_BIN := $(MAIN_ROOT)/node_modules/.bin/wp-env
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

# One shell for the whole recipe: each make recipe line gets its own shell, so an
# `exit 0` on the first line would not stop the later ones from linking anyway.
worktree-link: ## Symlink vendor/ and jetpack_vendor/ into this worktree from the main checkout
	@case "$(WORKTREE_REL)" in \
		"") \
			echo "Not a worktree, nothing to link.";; \
		/*) \
			echo "Error: this worktree is outside $(MAIN_ROOT)." >&2; \
			echo "wp-env only mounts the main checkout, so the container cannot see it." >&2; \
			echo "Create worktrees under .claude/worktrees/ to run tests in them." >&2; \
			exit 1;; \
		*) \
			up=$$(echo "$(WORKTREE_REL)" | sed 's#[^/][^/]*#..#g'); \
			for d in vendor jetpack_vendor; do \
				if [ -e "$$d" ] && [ ! -L "$$d" ]; then \
					echo "Skipping $$d, it is a real directory."; \
					continue; \
				fi; \
				ln -sfn "$$up/$$d" "$$d"; \
				echo "Linked $$d -> $$up/$$d"; \
			done; \
			echo "Relative links, so they resolve inside the container too.";; \
	esac

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
	@case "$(WORKTREE_REL)" in /*) \
		echo "Error: this worktree is outside $(MAIN_ROOT), so the container cannot see it." >&2; \
		echo "Create worktrees under .claude/worktrees/ to run tests in them." >&2; \
		exit 1;; esac
	@test -e vendor/bin/phpunit || { \
		echo "Error: vendor/ is missing in $(WORKTREE_ROOT)." >&2; \
		echo "Run 'make worktree-link' in a worktree, or 'make install' in a fresh clone." >&2; \
		exit 1; }
	@# wp-env keys its state directory on the directory it is invoked from, so it
	@# has to run from the main checkout. --env-cwd then points it at this one.
	cd $(MAIN_ROOT) && $(WP_ENV) run tests-cli --env-cwd=$(ENV_CWD) \
		bash -c 'WORDPRESS_DEVELOP_DIR=/wordpress-phpunit composer phpunit -- $(ARGS)'

test-acceptance: ## Run the Codeception acceptance suite (needs a configured WP + browser)
	composer tests

## Lint
lint: ## Run PHP CodeSniffer on changed files (vs trunk)
	@test -e vendor/bin/phpcs-changed || { \
		echo "Error: vendor/ is missing in $(WORKTREE_ROOT)." >&2; \
		echo "Run 'make worktree-link' in a worktree, or 'make install' in a fresh clone." >&2; \
		exit 1; }
	composer cs

lint-staged: ## Run PHP CodeSniffer on staged files
	@test -e vendor/bin/phpcs-changed || { \
		echo "Error: vendor/ is missing in $(WORKTREE_ROOT)." >&2; \
		echo "Run 'make worktree-link' in a worktree, or 'make install' in a fresh clone." >&2; \
		exit 1; }
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

.PHONY: install install-hooks worktree-link up down destroy logs cli wp test test-acceptance lint lint-staged lint-css release build clean i18n help
