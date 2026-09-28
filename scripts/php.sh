#!/usr/bin/env bash
# Runs a command inside a throwaway PHP container for one lib, so the PHP gates never
# depend on a workstation PHP. Composer installs use the composer image; everything else
# runs on the requested PHP version.
#
#   scripts/php.sh <php-version|composer> <lib-slug> <command...>
#   scripts/php.sh composer x06-cache-actions-child composer install
#   scripts/php.sh 7.4 x06-cache-actions-child vendor/bin/phpunit --testsuite unit
set -euo pipefail

if [ "$#" -lt 3 ]; then
  echo "usage: scripts/php.sh <php-version|composer> <lib-slug> <command...>" >&2
  exit 64
fi

version="$1"
lib="$2"
shift 2

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
lib_dir="$repo_root/libs/$lib"
if [ ! -d "$lib_dir" ]; then
  echo "php.sh: no such lib: libs/$lib" >&2
  exit 66
fi

if [ "$version" = "composer" ]; then
  image="composer:2"
else
  image="php:${version}-cli"
fi

if command -v cygpath >/dev/null 2>&1; then
  repo_root="$(cygpath -m "$repo_root")"
fi

# The whole repo is mounted so contract tests can read sibling libs.
MSYS_NO_PATHCONV=1 exec docker run --rm \
  -v "$repo_root:/repo" \
  -w "/repo/libs/$lib" \
  -e COMPOSER_ROOT_VERSION=dev-develop \
  "$image" \
  "$@"
