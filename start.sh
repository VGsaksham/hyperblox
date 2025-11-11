#!/usr/bin/env bash
set -euo pipefail

# Default port provided by Wasmer Cloud
PORT="${PORT:-8080}"
DOC_ROOT="."

echo "Starting PHP built-in server on port ${PORT} (doc root: ${DOC_ROOT})"

# Ensure writable data directory exists (for app flat files)
mkdir -p /data || true

# If app expects its generated folders in project root, bind a few common files into /data
# and symlink back so writes persist across restarts (best effort, non-fatal).
for f in visits.txt dailyvisits.txt; do
  if [ -f "${DOC_ROOT}/${f}" ] && [ ! -f "/data/${f}" ]; then
    cp "${DOC_ROOT}/${f}" "/data/${f}" || true
  fi
  if [ -f "/data/${f}" ] && [ ! -L "${DOC_ROOT}/${f}" ]; then
    rm -f "${DOC_ROOT}/${f}" && ln -s "/data/${f}" "${DOC_ROOT}/${f}" || true
  fi
done

# Launch PHP server (WASIX PHP provided by dependency)
exec php -S 0.0.0.0:${PORT} -t "${DOC_ROOT}"

