#!/usr/bin/env bash
# Runs every gate of one lib (PHP in containers, codegen drift, typecheck) and stops at the first failure.
#
#   scripts/lib-gates.sh <lib-slug>
#
# A lib opts into the PHP floor check by listing extra PHP versions in `php-gates.versions`
# (one per line) at its root; the unit suite and `php -l` run on each of them in addition
# to 8.3.
set -euo pipefail

if [ "$#" -lt 1 ]; then
  echo "usage: scripts/lib-gates.sh <lib-slug>" >&2
  exit 64
fi

lib="$1"
here="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
php="$here/php.sh"
lib_dir="$here/../libs/$lib"
local_env="$here/local-env.sh"

versions=("8.3")
if [ -f "$lib_dir/php-gates.versions" ]; then
  while IFS= read -r version; do
    [ -n "$version" ] && versions+=("$version")
  done < "$lib_dir/php-gates.versions"
fi

step() {
  echo "==> [$lib] $*"
}

step "composer install"
"$php" composer "$lib" composer install --no-interaction --no-progress --quiet

step "composer audit"
"$php" composer "$lib" composer audit --no-interaction

step "phpcs"
"$php" 8.3 "$lib" vendor/bin/phpcs -q

step "phpstan"
"$php" 8.3 "$lib" vendor/bin/phpstan analyse --no-progress --memory-limit=1G

for version in "${versions[@]}"; do
  step "php -l on PHP $version"
  "$php" "$version" "$lib" sh -c \
    'find . -path ./vendor -prune -o -name "*.php" -print0 | xargs -0 -n1 php -d display_errors=stderr -l > /dev/null'

  step "phpunit (unit) on PHP $version"
  "$php" "$version" "$lib" vendor/bin/phpunit --testsuite unit --bootstrap tests/bootstrap-unit.php
done

if [ -f "$lib_dir/package.json" ] && grep -q '"gen":' "$lib_dir/package.json"; then
  step "schema codegen drift"
  (cd "$lib_dir" && npm run --silent gen >/dev/null)
  # A local environment may produce gen:const output in a synced container; flush it first.
  if [ -f "$local_env" ]; then
    bash "$local_env" flush
  fi
  git -C "$lib_dir" add -N generated
  if ! git -C "$lib_dir" diff --exit-code --stat -- generated; then
    echo "lib-gates: libs/$lib/generated is stale; run npm run gen in libs/$lib and commit the result" >&2
    exit 1
  fi

  step "typecheck"
  (cd "$lib_dir" && npm run --silent typecheck)
  if grep -q '"test:js":' "$lib_dir/package.json"; then
    step "web unit tests"
    (cd "$lib_dir" && npm run --silent test:js)
  fi
fi

if compgen -G "$lib_dir/tests/Integration/*Test.php" >/dev/null; then
  if [ -f "$local_env" ]; then
    bash "$local_env" integration "$lib"
  else
    step "integration suite not run: it needs a local MainWP Dashboard (scripts/local-env.sh)"
  fi
fi

step "all gates passed"
