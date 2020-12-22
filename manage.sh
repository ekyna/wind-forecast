#!/bin/bash

source .env

# ----------------------------- VOLUME -----------------------------

VolumeExists() {
  if docker volume ls --format '{{.Name}}' | grep "${1}\$"; then
    return 0
  fi
  return 1
}

VolumeCreate() {
  if ! VolumeExists "${1}"; then
    if ! docker volume create --name "${1}"; then
      exit 1
    fi
  fi
}

Execute() {
  if [[ "$(uname -s)" == MINGW* ]]; then
    # shellcheck disable=SC2086
    winpty docker exec -u "${USER}" -it "${COMPOSE_PROJECT_NAME}_${1}" ${2}
  else
    # shellcheck disable=SC2086
    docker exec -u "${USER}" -it "${COMPOSE_PROJECT_NAME}_${1}" ${2}
  fi
}

SfCommand() {
  Execute php "php bin/console ${1}"
}

Composer() {
  Execute php "composer ${1}"
}

case $1 in
build)
  case $2 in
  php)
    docker build -f ./docker/php/Dockerfile \
      -t ekyna/wgrib-php \
      --build-arg WGRIB_VERSION="${WGRIB_VERSION}" \
      --build-arg USER="${USER}" \
      --build-arg GROUP="${GROUP}" \
      --build-arg USER_ID="${USER_ID}" \
      --build-arg GROUP_ID="${GROUP_ID}" \
      .
    ;;
  nginx)
    docker build -f ./docker/nginx/Dockerfile \
      -t ekyna/wgrib-nginx \
      --build-arg USER="${USER}" \
      --build-arg GROUP="${GROUP}" \
      --build-arg USER_ID="${USER_ID}" \
      --build-arg GROUP_ID="${GROUP_ID}" \
      .
    ;;
  *)
    printf "Usage: ./manage build [php|nginx]"
    ;;
  esac
  ;;
up)
  VolumeCreate "${COMPOSE_PROJECT_NAME}_public"
  VolumeCreate "${COMPOSE_PROJECT_NAME}_vendor"
  docker-compose -f compose.yml up -d
  ;;
down)
  docker-compose -f compose.yml down
  ;;
sf)
  SfCommand "${*:2}"
  ;;
composer)
  Composer "${*:2}"
  ;;
# ------------- HELP -------------
*)
  printf "Usage: ./manage.sh [args]
 - build [name] : Builds the [name] service image.
 "
  ;;
esac
