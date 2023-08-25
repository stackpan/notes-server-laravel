FROM php:8.1-fpm-alpine

RUN docker-php-ext-install pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install --optimize-autoloader --no-dev

RUN php artisan key:generate
RUN echo yes | php artisan jwt:secret

RUN php artisan route:cache
RUN php artisan view:cache

CMD sh ./run.sh
