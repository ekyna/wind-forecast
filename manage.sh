#!/bin/bash

source .env

COMPOSE_FILES="-f compose-${APP_ENV}.yml"

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

VolumeRemove() {
  if VolumeExists "${1}"; then
    if ! docker volume rm "${1}"; then
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
    docker build -f "./docker/php/${APP_ENV}/Dockerfile" -t "ekyna/wgrib-php-${APP_ENV}" .
    ;;
  nginx)
    docker build -f ./docker/nginx/Dockerfile -t ekyna/wgrib-nginx .
    ;;
  *)
    printf "Usage: ./manage build [php|nginx]"
    ;;
  esac
  ;;
up)
  VolumeCreate "${COMPOSE_PROJECT_NAME}_public"
  VolumeCreate "${COMPOSE_PROJECT_NAME}_vendor"
  docker-compose ${COMPOSE_FILES} up -d
  if [[ "${APP_ENV}" == "prod" ]]; then
    Composer "install --prefer-dist --no-interaction --no-progress --no-suggest"
  fi
  ;;
down)
  docker-compose ${COMPOSE_FILES} down -v --remove-orphans
  ;;
clear)
  docker-compose ${COMPOSE_FILES} down -v --remove-orphans
  VolumeRemove "${COMPOSE_PROJECT_NAME}_public"
  VolumeRemove "${COMPOSE_PROJECT_NAME}_vendor"
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
 - build [php|nginx]    Builds the service image.
 - up                   Starts the services.
 - down                 Stops the services.
 - clear                Stops the services and deletes the volumes.
 - sf [cmd]             Runs the symfony command.
 - composer [cmd]       Runs the composer command.
 "
  ;;
esac
