#!/usr/bin/env bash
uid=$(id -u)
gid=$(id -g)

printf "UID=${uid}\nGID=${gid}\nCOMPOSE_PROJECT_NAME=gateway" > .env

docker-compose up -d --build

docker-compose exec -T php-fpm.stepup.example.com bash -c '
  cp ./ci/config/*.yml ./app/config/
  cp ./ci/certificates/* ./app/
  cp ./ci/app.php ./web/app.php
  composer install --prefer-dist -n -o --no-scripts && \
  composer distribution-bundle-scripts && \
  ./app/console mopa:bootstrap:symlink:less --env=test && \
  ./app/console assetic:dump --env=test --verbose
'
