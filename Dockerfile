# NeNe Serve — runtime image (scaffold, #10)
FROM php:8.4-cli

RUN docker-php-ext-install pdo_mysql \
    && apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . /app

# Serve owns the 80xx lane and the API port is fixed at 8010 (CLAUDE.md port
# registry, README "Local ports"). It is hardcoded rather than read from
# APP_PORT because docker-compose.yml hardcodes it too, and a CMD that can bind
# somewhere EXPOSE does not name is the same drift in a new place. If the lane
# moves again, tests/Runtime/ContainerPortConsistencyTest.php fails until every
# copy moves with it.
EXPOSE 8010

# Front controller via PHP built-in server; nginx/apache fronting lands later.
CMD ["php", "-S", "0.0.0.0:8010", "-t", "public_html", "public_html/index.php"]
