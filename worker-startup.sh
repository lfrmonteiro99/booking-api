#!/bin/bash

# Set proper permissions
chown -R www-data:www-data /var/www/html
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Install dependencies and start queue worker
composer install --no-scripts --no-autoloader
composer dump-autoload --optimize
php artisan queue:work 