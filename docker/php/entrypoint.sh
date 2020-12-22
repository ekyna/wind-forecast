#!/bin/bash
set -e

# see https://wiki.alpinelinux.org/wiki/Setting_the_timezone
if [[ -n ${TZ} ]] && [[ -f /usr/share/zoneinfo/${TZ} ]]
then
    cp /usr/share/zoneinfo/${TZ} /etc/localtime
    echo ${TZ} > /etc/timezone
fi

if [[ "$1" == '/usr/bin/php-fpm7' ]]
then
  for f in /entrypoint.d/*
  do
    case "$f" in
      *.sh) echo "$0: running $f"; . "$f" ;;
      *)    echo "$0: ignoring $f" ;;
    esac
  done
fi

#composer install --prefer-dist --no-dev --no-interaction --no-progress

exec "$@"
