#!/usr/bin/env bash
# Maps a release tag to the plugin it releases, as GitHub Actions step outputs.
#
#   scripts/release/resolve-tag.sh v0.2.0        # dashboard extension
#   scripts/release/resolve-tag.sh child-v0.2.0  # companion
set -euo pipefail

tag="${1:?usage: resolve-tag.sh <tag>}"

if [[ "$tag" =~ ^child-v([0-9]+\.[0-9]+\.[0-9]+)$ ]]; then
  lib="x06-cache-actions-child"
  title="X06 Cache Actions Child"
elif [[ "$tag" =~ ^v([0-9]+\.[0-9]+\.[0-9]+)$ ]]; then
  lib="x06-cache-actions"
  title="X06 Cache Actions"
else
  echo "resolve-tag: '$tag' is neither v<x.y.z> nor child-v<x.y.z>" >&2
  exit 65
fi

version="${BASH_REMATCH[1]}"
echo "lib=$lib"
echo "version=$version"
echo "title=$title $version"
