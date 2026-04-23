#!/usr/bin/env bash

set -euo pipefail

DOCKER_COMPOSE_FILE="docker-compose-test.yml"
SCRIPT_EXIT_CODE=0

teardown_test_environment() {
    if ! docker compose -f "$DOCKER_COMPOSE_FILE" down; then
        echo "Warning: Failed to shut down docker compose test services." >&2
        if [ "$SCRIPT_EXIT_CODE" -eq 0 ]; then
            SCRIPT_EXIT_CODE=1
        fi
    fi

    trap - EXIT
    exit "$SCRIPT_EXIT_CODE"
}

trap teardown_test_environment EXIT

run_step() {
    set +e
    "$@"
    local exit_code=$?
    set -e

    if [ "$exit_code" -ne 0 ]; then
        SCRIPT_EXIT_CODE=$exit_code
        exit "$SCRIPT_EXIT_CODE"
    fi
}

if [ "${GITHUB_ACTIONS:-false}" = "true" ]; then
    run_step docker compose -f "$DOCKER_COMPOSE_FILE" build \
        --build-arg BUILDKIT_INLINE_CACHE=1
else
    run_step docker compose -f "$DOCKER_COMPOSE_FILE" build
fi

run_step docker compose -f "$DOCKER_COMPOSE_FILE" up -d --wait
run_step vendor/bin/pest
