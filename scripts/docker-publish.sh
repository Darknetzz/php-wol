#!/usr/bin/env bash
set -euo pipefail

IMAGE="${DOCKER_IMAGE:-darknetz/php-wol}"
VERSION="${1:-}"

if [[ -z "$VERSION" ]]; then
  echo "Usage: $0 <version>" >&2
  echo "Example: $0 2.1.0" >&2
  exit 1
fi

if ! docker info >/dev/null 2>&1; then
  echo "Error: Docker daemon is not reachable." >&2
  exit 1
fi

if ! grep -q 'index.docker.io' "${HOME}/.docker/config.json" 2>/dev/null; then
  echo "Error: Not logged in to Docker Hub. Run: docker login" >&2
  exit 1
fi

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

echo "Building ${IMAGE}:${VERSION} ..."
docker build -t "${IMAGE}:${VERSION}" -t "${IMAGE}:latest" .

echo "Pushing ${IMAGE}:${VERSION} ..."
docker push "${IMAGE}:${VERSION}"

echo "Pushing ${IMAGE}:latest ..."
docker push "${IMAGE}:latest"

echo "Published ${IMAGE}:${VERSION} and ${IMAGE}:latest"
