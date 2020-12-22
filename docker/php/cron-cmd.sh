#!/bin/sh
set -e

crond -s /var/spool/cron/crontabs -f -l info
