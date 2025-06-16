FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libssl-dev \
    zlib1g-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sockets \
    && pecl install redis \
    && docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy startup scripts
COPY startup.sh /usr/local/bin/startup.sh
COPY worker-startup.sh /usr/local/bin/worker-startup.sh
RUN chmod +x /usr/local/bin/startup.sh /usr/local/bin/worker-startup.sh

# Copy composer files
COPY composer.json composer.lock* ./

# Install dependencies
RUN composer install --no-scripts --no-autoloader

# Copy the rest of the application
COPY . .

# Generate autoload files
RUN composer dump-autoload --optimize

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Start PHP-FPM with startup script
CMD ["/usr/local/bin/startup.sh"] 