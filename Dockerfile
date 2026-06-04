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

EXPOSE 8910

# Front controller via PHP built-in server; nginx/apache fronting lands later.
CMD ["php", "-S", "0.0.0.0:8910", "-t", "public_html", "public_html/index.php"]
