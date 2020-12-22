FROM nginx:stable-alpine

MAINTAINER Etienne Dauvergne <contact@ekyna.com>

ENV USER="wgrib"
ENV USER_ID=1000
ENV GROUP="wgrib"
ENV GROUP_ID=1000

RUN addgroup ${GROUP} -g ${GROUP_ID} \
 && adduser -D -u ${USER_ID} -h /var/www -s /bin/bash -G ${GROUP} ${USER}

WORKDIR /var/www

COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf

RUN mkdir -m 0755 -p /var/www/public \
 && chown -Rf ${USER}:${GROUP} /var/www
