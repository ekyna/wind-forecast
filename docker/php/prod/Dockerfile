FROM area51/alpine-dev AS base

ENV WGRIB_VERSION="3.0.0"
ENV FILE_TYPE ""
ENV LINK_FILE_TO_DOWNLOAD ""
ENV GRIB_PARAMS ""
ENV ROUTING_KEY_PARSE_PREFIX ""
ENV GRIB_POSITION ""
ENV OTHER ""
ENV DEBUG true

ENV CC=gcc
ENV FC=gfortran

RUN apk add --no-cache \
      zlib-dev

RUN wget -q -O /tmp/wgrib2.tgz \
      ftp://ftp.cpc.ncep.noaa.gov/wd51we/wgrib2/wgrib2.tgz.v${WGRIB_VERSION}

RUN mkdir -p /opt && \
    cd /opt/ && \
    tar -xf /tmp/wgrib2.tgz

RUN cd /opt/grib2 && \
    make

# ======================================================================
FROM alpine:3.12

ENV USER="wgrib"
ENV USER_ID=1000
ENV GROUP="wgrib"
ENV GROUP_ID=1000

ENV TZ="UTC" \
    LANG="C.UTF-8" \
    COMPOSER_MIRROR_PATH_REPOS=1

RUN apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/community/ --allow-untrusted gnu-libiconv
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

RUN apk add --no-cache \
      ca-certificates \
      bash \
      curl \
      dcron \
      libgfortran \
      libgomp \
      php7 \
      php7-curl \
      php7-ctype \
      php7-dom \
      php7-fpm \
      php7-iconv \
      php7-json \
      php7-openssl \
      php7-phar \
      php7-session \
      php7-xml \
      php7-simplexml \
      php7-tokenizer \
    && rm -rf /var/cache/apk/* \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (trim(hash_file('SHA384', 'composer-setup.php')) !== trim(file_get_contents('https://composer.github.io/installer.sig'))) { echo 'Composer installer corrupt' . PHP_EOL; exit(1); }" \
    && php composer-setup.php --quiet --install-dir=/usr/bin --filename=composer \
    && php -r "unlink('composer-setup.php');" \
    && addgroup ${GROUP} -g ${GROUP_ID} \
    && adduser -D -u ${USER_ID} -h /var/www -s /bin/bash -G ${GROUP} ${USER}

COPY --from=base /opt/grib2/wgrib2/wgrib2 /usr/local/bin/wgrib2
COPY docker/php/php.ini /etc/php7/conf.d/51-setting.ini
COPY docker/php/php-fpm.conf /etc/php7/php-fpm.conf
COPY docker/php/cron-cmd.sh /cron-cmd.sh
COPY docker/php/cron-entry.sh /cron-entry.sh
COPY docker/php/crontab /etc/crontabs/${USER}
COPY docker/php/entrypoint.sh /entrypoint.sh
COPY symfony /var/www

RUN chmod +x /entrypoint.sh /cron-cmd.sh /cron-entry.sh \
 && mkdir -p /entrypoint.d \
 && mkdir -p /etc/cron.d \
 && mkdir -p /var/www/var/cache \
 && mkdir -p /var/www/var/log \
 && mkdir -p /var/www/var/tmp \
 && mkdir -p /var/www/var/wind \
 && mkdir -p /var/www/vendor \
 && chmod -R 744 /var/www \
 && chown -R ${USER}:${GROUP} /var/www \
 && chown root:root /etc/cron.d \
 && chown root:root /etc/crontabs/${USER} \
 && chmod 444 /etc/crontabs/${USER}

WORKDIR /var/www

EXPOSE 9000

ENTRYPOINT ["sh", "/entrypoint.sh" ]

CMD ["/usr/sbin/php-fpm7", "-F"]
