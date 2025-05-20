
FROM php:8.3-fpm

# Устанавливаем необходимые зависимости
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    cron \
    nginx \
    && docker-php-ext-install zip intl pdo pdo_pgsql

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Настройка Cron для отправки погодных обновлений
COPY docker/weather-cron /etc/cron.d/weather-cron
RUN chmod 0644 /etc/cron.d/weather-cron \
    && crontab /etc/cron.d/weather-cron \
    && touch /var/log/cron.log

# Создаем директорию для приложения
WORKDIR /var/www/html

# Копируем файлы приложения
COPY . .

# Устанавливаем зависимости
RUN composer install --no-interaction --no-progress --optimize-autoloader

# Настройка прав доступа
RUN chmod +x bin/console \
    && chown -R www-data:www-data var

# Запускаем PHP-FPM по умолчанию
#CMD ["php-fpm"]
CMD ["sh", "-c", "printenv > /etc/environment && cron && php-fpm"]
