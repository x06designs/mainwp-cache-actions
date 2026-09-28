#!/usr/bin/env bash
# Sets a plugin's version everywhere the release workflow checks it: the plugin header, the
# version constant and, for the dashboard extension, package.json and generated/constants.ts.
# Commit the result, merge it to main, then tag (v<version> or child-v<version>).
#
#   scripts/release/set-version.sh x06-cache-actions 0.2.0
#   scripts/release/set-version.sh x06-cache-actions-child 0.2.0
set -euo pipefail

lib="${1:?usage: set-version.sh <lib> <x.y.z>}"
version="${2:?usage: set-version.sh <lib> <x.y.z>}"

[[ "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "set-version: '$version' is not x.y.z" >&2; exit 65; }

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
lib_dir="$repo_root/libs/$lib"
main="$lib_dir/$lib.php"
[ -f "$main" ] || { echo "set-version: no plugin main file $main" >&2; exit 66; }

sed -i -E \
  -e "s/^( \* Version:[[:space:]]+).*/\1$version/" \
  -e "s/^(const [A-Z0-9_]+_VERSION[[:space:]]+= ')[^']*(';)/\1$version\2/" \
  "$main"

if [ "$lib" = "x06-cache-actions" ]; then
  sed -i -E "s/^(  \"version\": \")[^\"]*(\",)/\1$version\2/" "$lib_dir/package.json"
  (cd "$repo_root" && npx nx run "$lib:gen" --skip-nx-cache)
fi

grep -nE "^ \* Version:|_VERSION " "$main"
