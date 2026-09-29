#!/usr/bin/env bash
# Run on the VPS to pull the latest code and rebuild the containers.
set -euo pipefail
cd "$(dirname "$0")"

git pull --ff-only
docker compose -f docker-compose.prod.yml up -d --build
docker image prune -f
