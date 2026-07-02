#!/usr/bin/env bash
# TaskSync Pro — Production DB initialization script
# Runs once on first PostgreSQL container start via docker-entrypoint-initdb.d

set -e

echo "=== Creating TaskSync Pro extensions ==="
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE EXTENSION IF NOT EXISTS pg_trgm;
    CREATE EXTENSION IF NOT EXISTS pgcrypto;
    CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
EOSQL

echo "=== Extensions created ==="
