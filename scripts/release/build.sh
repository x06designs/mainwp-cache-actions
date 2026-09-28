#!/usr/bin/env bash
# Builds a plugin's release zip at tmp/release/<lib>.zip, with <lib>/ as the zip's root folder and
# production Composer dependencies only. Needs `php` (with zip) and `composer` on PATH; for the
# dashboard extension, build the web layer first (`npx nx run x06-cache-actions:build`).
#
#   scripts/release/build.sh <lib> <x.y.z>
#
# Locally, without PHP on the workstation:
#   MSYS_NO_PATHCONV=1 docker run --rm -v "$(pwd -W):/repo" -w /repo composer:2 \
#     bash scripts/release/build.sh <lib> <x.y.z>
set -euo pipefail

lib="${1:?usage: build.sh <lib> <x.y.z>}"
version="${2:?usage: build.sh <lib> <x.y.z>}"

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$repo_root"

src="libs/$lib"
stage="tmp/release/$lib"
zip="tmp/release/$lib.zip"
main="$src/$lib.php"

die() { echo "build: $*" >&2; exit 1; }

case "$lib" in
  x06-cache-actions)
    files=(x06-cache-actions.php uninstall.php includes languages build composer.json composer.lock README.md)
    required=(x06-cache-actions.php vendor/autoload.php vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php build/manifest.json)
    [ -f "$src/build/manifest.json" ] || die "$src/build/manifest.json missing; run npx nx run x06-cache-actions:build first"
    [ ! -e "$src/build/vite-dev-server.json" ] || die "$src/build/vite-dev-server.json present; stop the dev server and rebuild"
    [ -z "$(find "$src/build" -name '*.map' -print -quit)" ] || die "source maps in $src/build; rebuild with sourcemap off"
    package_version="$(sed -n -E 's/^  "version": "([^"]*)",$/\1/p' "$src/package.json")"
    [ "$package_version" = "$version" ] || die "$src/package.json has version '$package_version', expected '$version'"
    ;;
  x06-cache-actions-child)
    files=(x06-cache-actions-child.php includes composer.json composer.lock README.md)
    required=(x06-cache-actions-child.php includes/autoload.php vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php)
    ;;
  *)
    die "unknown lib '$lib'"
    ;;
esac

header_version="$(sed -n -E 's/^ \* Version:[[:space:]]+//p' "$main")"
[ "$header_version" = "$version" ] || die "$main header has version '$header_version', expected '$version'"
grep -qE "^const [A-Z0-9_]+_VERSION[[:space:]]+= '$version';" "$main" || die "$main version constant is not '$version'"

rm -rf "$stage" "$zip"
mkdir -p "$stage"
for f in "${files[@]}"; do
  cp -R "$src/$f" "$stage/$f"
done

COMPOSER_ROOT_VERSION="$version" composer install --working-dir="$stage" --no-dev --optimize-autoloader --no-interaction --no-progress
rm -f "$stage/composer.lock"

php scripts/release/zip.php "$stage" "$lib" "$zip" "${required[@]}"
