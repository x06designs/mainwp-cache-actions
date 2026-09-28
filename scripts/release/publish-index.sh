#!/usr/bin/env bash
# Adds a published release to packages.json on the gh-pages branch (the Composer repository at
# https://x06designs.github.io/mainwp-cache-actions/) and pushes it. Runs in the release workflow.
#
#   scripts/release/publish-index.sh <lib> <x.y.z> <tag>
set -euo pipefail

lib="${1:?usage: publish-index.sh <lib> <x.y.z> <tag>}"
version="${2:?usage: publish-index.sh <lib> <x.y.z> <tag>}"
tag="${3:?usage: publish-index.sh <lib> <x.y.z> <tag>}"
: "${GITHUB_REPOSITORY:?GITHUB_REPOSITORY is not set}"

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$repo_root"

pages="tmp/gh-pages"
zip="tmp/release/$lib.zip"
url="https://github.com/$GITHUB_REPOSITORY/releases/download/$tag/$lib.zip"

rm -rf "$pages"
git worktree prune
if git ls-remote --exit-code --heads origin gh-pages >/dev/null; then
  git fetch --no-tags origin gh-pages
  git worktree add -B gh-pages "$pages" origin/gh-pages
else
  git worktree add --orphan -b gh-pages "$pages"
fi

php scripts/release/composer-index.php "$pages/packages.json" "$lib" "$version" "$url" "$zip"
touch "$pages/.nojekyll"

git -C "$pages" add packages.json .nojekyll
git -C "$pages" -c user.name='github-actions[bot]' \
  -c user.email='41898282+github-actions[bot]@users.noreply.github.com' \
  commit -m "chore(release): add $tag to the Composer index"
git -C "$pages" push origin gh-pages
