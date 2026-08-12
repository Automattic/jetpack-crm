#!/usr/bin/env bash
#
# Runs phpcs with PHP 8.4+ deprecations from thecodingmachine/safe suppressed.
#
# phpcs boots the project's Composer autoloader, which eagerly loads
# thecodingmachine/safe's generated function files. On PHP 8.4 and up those
# emit an implicit-nullable deprecation each, and the notices land on stdout
# ahead of phpcs's own output. That is fatal for phpcs-changed, which parses
# phpcs's JSON: it fails to decode, assumes the unmodified file had no errors,
# and reports every pre-existing violation in the file as though you'd
# introduced it.
#
# So phpcs is never called directly. `composer cs` and friends point
# phpcs-changed at this wrapper via --phpcs-path.
#
# Same fix, and same prepend file, as the phpunit and codecept scripts in
# composer.json. It goes away when the plugin can drop PHP 7.4 and move to
# thecodingmachine/safe v2.

set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

exec php \
	-d auto_prepend_file="${repo_root}/tests/suppress_php84_deprecations.php" \
	"${repo_root}/vendor/bin/phpcs" "$@"
