#!/usr/bin/env bash
# Restore a PostgreSQL dump and, optionally, the MinIO and storage archives.
# Usage: ./infrastructure/restore.sh backups/postgres-<stamp>.sql.gz \
#          [backups/minio-<stamp>.tar.gz] [backups/storage-<stamp>.tar.gz]
set -euo pipefail

if [[ $# -lt 1 ]]; then
  echo "Usage: $0 <postgres.sql.gz> [minio.tar.gz] [storage.tar.gz]" >&2
  exit 1
fi

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

COMPOSE_FILE="${COMPOSE_FILE:-infrastructure/compose.yaml}"
PROJECT="${COMPOSE_PROJECT_NAME:-meridian}"
POSTGRES_DUMP="$1"
MINIO_ARCHIVE="${2:-}"
STORAGE_ARCHIVE="${3:-}"

echo "Pausing application services"
docker compose -f "$COMPOSE_FILE" stop api queue scheduler website studio nginx

echo "Restoring PostgreSQL from $POSTGRES_DUMP"
gunzip -c "$POSTGRES_DUMP" | docker compose -f "$COMPOSE_FILE" exec -T postgres \
  sh -c 'psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d "$POSTGRES_DB"'

if [[ -n "$MINIO_ARCHIVE" ]]; then
  echo "Restoring MinIO data from $MINIO_ARCHIVE"
  docker run --rm \
    -v "${PROJECT}_minio-data:/data" \
    -v "$ROOT_DIR/$(dirname "$MINIO_ARCHIVE"):/backup:ro" \
    alpine:3.20 sh -c "rm -rf /data/* && tar xzf /backup/$(basename "$MINIO_ARCHIVE") -C /data"
fi

if [[ -n "$STORAGE_ARCHIVE" ]]; then
  echo "Restoring application storage from $STORAGE_ARCHIVE"
  docker run --rm \
    -v "${PROJECT}_api-storage:/data" \
    -v "$ROOT_DIR/$(dirname "$STORAGE_ARCHIVE"):/backup:ro" \
    alpine:3.20 sh -c "rm -rf /data/* && tar xzf /backup/$(basename "$STORAGE_ARCHIVE") -C /data"
fi

echo "Starting application services"
docker compose -f "$COMPOSE_FILE" up -d postgres redis minio meilisearch
docker compose -f "$COMPOSE_FILE" up -d api queue scheduler nginx website studio

echo "Rebuilding the search index"
docker compose -f "$COMPOSE_FILE" exec -T api php artisan search:index

echo "Restore finished. Verify /api/v1/health, the website, and Studio before announcing recovery."
