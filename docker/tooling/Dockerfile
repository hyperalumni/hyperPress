FROM php:8.5-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install intl

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN git config --system --add safe.directory '*'

WORKDIR /themes
