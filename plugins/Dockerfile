FROM php:8.5-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip mariadb-client \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install mysqli

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
