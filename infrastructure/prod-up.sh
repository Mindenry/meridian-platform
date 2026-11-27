#!/usr/bin/env bash
# Start the production API stack. The website and Studio are hosted on Netlify
# and are intentionally not started here.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if [ -z "${APP_URL:-}" ] && ! grep -q '^APP_URL=https://' infrastructure/.env 2>/dev/null; then
  echo "Set APP_URL (for example https://api.example.com) in the shell or infrastructure/.env." >&2
  exit 1
fi

docker compose -f infrastructure/compose.yaml -f infrastructure/compose.prod.yaml \
  up -d --build postgres redis meilisearch minio api queue scheduler nginx

docker compose -f infrastructure/compose.yaml -f infrastructure/compose.prod.yaml \
  exec -T api php artisan migrate --force

docker compose -f infrastructure/compose.yaml -f infrastructure/compose.prod.yaml \
  exec -T api php artisan search:index || echo "Search index failed; the API will fall back to database search." >&2

echo "API stack is up at ${APP_URL:-the configured APP_URL}"
