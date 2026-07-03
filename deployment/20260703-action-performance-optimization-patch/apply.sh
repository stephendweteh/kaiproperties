#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
PATCH_FILE="$SCRIPT_DIR/action-performance-optimization.patch"

cd "$REPO_ROOT"

echo "Applying patch: $PATCH_FILE"

if [ ! -f "$PATCH_FILE" ]; then
  echo "ERROR: patch file not found: $PATCH_FILE" >&2
  exit 1
fi

if command -v git >/dev/null 2>&1; then
  if git apply --check "$PATCH_FILE"; then
    git apply "$PATCH_FILE"
    echo "Patch applied with git apply"
    exit 0
  fi

  if git apply --3way "$PATCH_FILE"; then
    echo "Patch applied with git apply --3way"
    exit 0
  fi
fi

if command -v patch >/dev/null 2>&1; then
  if patch -p1 --forward < "$PATCH_FILE"; then
    echo "Patch applied with patch -p1 --forward"
    exit 0
  fi
fi

echo "ERROR: patch could not be applied automatically." >&2
echo "Rebase or regenerate patch against the current code state." >&2
exit 1
