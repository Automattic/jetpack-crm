#!/usr/bin/env bash
#
# Runs phpcs-changed over the PHP files changed in one of three ways, so that
# only the lines you touched are reported.
#
# The three modes were three near-identical one-liners in composer.json. What
# they have in common is their error handling, and that is the part worth
# keeping in one place: the shape this replaced was
#
#   [[ -n $temp ]] && phpcs-changed ... || echo 'No changes found.'
#
# which sent any failure down the `||` branch and exited 0, so lint reported
# success while linting nothing. One copy is one copy to get right.
#
# phpcs itself is reached through scripts/phpcs.sh, which is where the PHP 8.4+
# deprecation handling lives. See the comment there.

set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${repo_root}"

case "${1:-}" in
	base)
		# Three dots, not two. phpcs-changed resolves --git-base to the merge
		# base, so a two-dot diff here would also queue files that changed on
		# trunk since the branch point and lint them for nothing.
		diff_args=( trunk...HEAD )
		changed_args=( --git-base trunk )
		read_error="Could not diff against trunk."
		;;
	staged)
		diff_args=( --cached )
		changed_args=( --git-staged )
		read_error="Could not read staged changes."
		;;
	unstaged)
		diff_args=()
		changed_args=( --git-unstaged )
		read_error="Could not read unstaged changes."
		;;
	*)
		echo "Usage: ${BASH_SOURCE[0]##*/} base|staged|unstaged" >&2
		exit 2
		;;
esac

if ! files="$(git diff --diff-filter=d --name-only "${diff_args[@]}" -- '*.php')"; then
	echo "${read_error}" >&2
	exit 1
fi

if [ -z "${files}" ]; then
	echo "No PHP changes found."
	exit 0
fi

# Read into an array rather than letting the shell split on whitespace, so a
# path with a space in it is one file and not two. `mapfile` would be tidier,
# but macOS still ships bash 3.2 and the Makefile runs recipes through it.
file_list=()
while IFS= read -r file; do
	[ -n "${file}" ] && file_list+=( "${file}" )
done <<< "${files}"

# Say how many files are being looked at. A clean run prints nothing at all
# otherwise, which leaves silence meaning both "linted and found nothing" and
# "linted nothing" -- the ambiguity this script exists to remove.
echo "Linting ${#file_list[@]} changed PHP file(s)..."

# display_errors=stderr for the same reason scripts/phpcs.sh sets it. Nothing
# parses this process's stdout, so this one is only about legibility: a
# deprecation printed into the middle of a report is noise you have to read
# past to find the violations.
exec php \
	-d display_errors=stderr \
	-d auto_prepend_file="${repo_root}/tests/suppress_php84_deprecations.php" \
	"${repo_root}/vendor/bin/phpcs-changed" \
	-s --git "${changed_args[@]}" \
	--phpcs-path "${repo_root}/scripts/phpcs.sh" \
	"${file_list[@]}"
