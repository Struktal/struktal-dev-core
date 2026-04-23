#!/usr/bin/env bash

set -euo pipefail

DOCKER_COMPOSE_FILE="docker-compose-test.yml"

cleanup() {
    docker compose -f "$DOCKER_COMPOSE_FILE" down
}

trap cleanup EXIT

if [ "${GITHUB_ACTIONS:-false}" = "true" ]; then
    docker compose -f "$DOCKER_COMPOSE_FILE" build \
        --build-arg BUILDKIT_INLINE_CACHE=1
else
    docker compose -f "$DOCKER_COMPOSE_FILE" build
fi

docker compose -f "$DOCKER_COMPOSE_FILE" up -d --wait

vendor/bin/pest
