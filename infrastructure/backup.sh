#!/usr/bin/env bash
# Back up PostgreSQL data and the media volumes used by the Compose stack.
# Usage: ./infrastructure/backup.sh
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

COMPOSE_FILE="${COMPOSE_FILE:-infrastructure/compose.yaml}"
PROJECT="${COMPOSE_PROJECT_NAME:-meridian}"
OUT_DIR="${BACKUP_DIR:-backups}"
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"

mkdir -p "$OUT_DIR"

echo "Backing up PostgreSQL to $OUT_DIR/postgres-$STAMP.sql.gz"
docker compose -f "$COMPOSE_FILE" exec -T postgres \
  sh -c 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --clean --if-exists' \
  | gzip > "$OUT_DIR/postgres-$STAMP.sql.gz"

echo "Backing up MinIO data volume"
docker run --rm \
  -v "${PROJECT}_minio-data:/data:ro" \
  -v "$ROOT_DIR/$OUT_DIR:/backup" \
  alpine:3.20 tar czf "/backup/minio-$STAMP.tar.gz" -C /data .

echo "Backing up application storage volume"
docker run --rm \
  -v "${PROJECT}_api-storage:/data:ro" \
  -v "$ROOT_DIR/$OUT_DIR:/backup" \
  alpine:3.20 tar czf "/backup/storage-$STAMP.tar.gz" -C /data .

echo "Done. Files:"
ls -lh "$OUT_DIR"/*"$STAMP"*
