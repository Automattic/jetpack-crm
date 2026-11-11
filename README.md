Jetpack CRM - Core CRM Plugin - WordPress.org Hosted

----
https://wordpress.org/plugins/zero-bs-crm/
----

![](https://github.com/Automattic/zero-bs-crm/workflows/Deploy%20to%20WordPress.org%20Repo/badge.svg)
----

[![WP compatibility](https://plugintests.com/plugins/zero-bs-crm/wp-badge.svg)](https://plugintests.com/plugins/zero-bs-crm/latest)
[![PHP compatibility](https://plugintests.com/plugins/zero-bs-crm/php-badge.svg)](https://plugintests.com/plugins/zero-bs-crm/latest)
([Plugin Smoke Test](https://plugintests.com/plugins/zero-bs-crm/latest))

## Development

The `Makefile` wraps the common development tasks. Run `make help` to list every
target.

### Prerequisites

- [Docker](https://www.docker.com/) (running) — the local WordPress environment
  uses [`@wordpress/env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/).
- Node.js and npm.
- Composer.

### First-time setup

```sh
make install        # install Composer (PHP) and npm (JS) dependencies
make install-hooks  # optional: install the git pre-push hook (blocks pushes to trunk)
```

### Run WordPress in Docker

```sh
make up       # start WordPress at http://localhost:8888 (admin / password)
make down     # stop the containers (keeps the database)
make destroy  # remove the containers and the database
```

The plugin is mounted into the container, so edits are picked up live (run
`make build` first if you change compiled assets — see below).

### Other useful targets

| Target | What it does |
| --- | --- |
| `make logs` | Tail the WordPress container logs |
| `make cli` | Open a shell inside the `cli` container |
| `make wp CMD="plugin list"` | Run a wp-cli command in the container |
| `make test` | Run the PHPUnit unit suite (needs `make up` first) |
| `make lint` | Run PHP CodeSniffer on files committed on this branch vs `trunk` |
| `make lint-staged` | Run PHP CodeSniffer on staged changes |
| `make lint-unstaged` | Run PHP CodeSniffer on unstaged working tree changes |
| `make build` | Build `dist/zero-bs-crm.zip` (tracked source from `HEAD`, assets compiled fresh) |
| `make clean` | Remove the `dist/` staging directory |

### Tests

`make test` runs inside the wp-env `tests-cli` container, so bring the
environment up first with `make up`. It takes phpunit flags via `ARGS`:

```sh
make test                             # the whole unit suite
make test ARGS="--testsuite pdf"      # one suite
make test ARGS="--filter Company"     # one test or class
```

### Linting

Every lint target reports only on the lines you touched, not on the whole file,
because the tree as a whole does not pass the ruleset in `.phpcs.xml.dist`. A
run that finds nothing says `No PHP changes found.`; a run that finds something
exits non-zero.

`make lint` covers what you have committed on this branch. Uncommitted work is
`make lint-staged` and `make lint-unstaged`. Neither phpcs nor phpunit runs in
CI yet, so these are local-only.

### Working in a git worktree

Both `make test` and `make lint` work from a worktree under `.claude/worktrees/`,
which is inside the directory wp-env mounts. `vendor/`, `jetpack_vendor/` and
`node_modules/` are gitignored and so absent there — run `make worktree-link`
once to symlink them in from the main checkout. Note what that shares: npm and
composer run in a worktree write into the main checkout.

wp-env itself only runs from the main checkout, since it keys its state
directory on the invocation directory. A worktree placed anywhere other than
inside the main checkout can't work at all, and the targets say so rather than
falling back. Neither is a checkout path containing a space supported.

## Publishing a release

Releases are cut from this repository. The flow is two steps: prepare a release
PR locally, then merge it to trigger the automated release.

### 1. Prepare the release PR

Before running, make sure:

- Your working tree is clean (the command refuses to run otherwise).
- You are authenticated with the GitHub CLI (`gh auth login`).
- A GitHub **milestone** named after the version (e.g. `6.9.0`) exists, with the
  merged PRs to include assigned to it. The changelog is assembled from those PRs.

```sh
make release VERSION=6.9.0
```

This bumps the version everywhere it appears (plugin header, `JPCRM_VERSION`,
`readme.txt` stable tag, `package.json`), assembles the changelog, and opens a
`release/zero-bs-crm-6.9.0` PR. You can **edit the changelog in the PR
description** in the browser before merging.

### 2. Merge the PR

Merging the release PR triggers `.github/workflows/create-release.yml`, which:

1. writes the (edited) changelog from the PR body into `readme.txt`,
2. builds the plugin (`make build`),
3. tags the release and creates the GitHub release (with the built `zero-bs-crm.zip` attached), and
4. deploys the built plugin to WordPress.org.

The WordPress.org deploy requires the `WORDPRESSORG_SVN_USERNAME` and
`WORDPRESSORG_SVN_PASSWORD` repository secrets to be set.
