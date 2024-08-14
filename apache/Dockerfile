FROM php:7.4-apache

# Update package list and install dependencies
RUN apt-get update && \
    apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    zlib1g-dev \
    libicu-dev \
    g++ \
    unzip \
    libonig-dev \
    && apt-get clean

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd mbstring soap zip mysqli intl

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set the working directory
WORKDIR /var/www/html

# Download and extract Moodle
RUN curl -L https://download.moodle.org/download.php/direct/stable39/moodle-latest-39.tgz -o moodle.tgz && \
    tar -zxvf moodle.tgz && \
    mv moodle/* /var/www/html/ && \
    rm -rf moodle moodle.tgz && \
    chown -R www-data:www-data /var/www/html

# Ensure proper permissions for /var/www
RUN chown -R www-data:www-data /var/www && \
    chmod -R 755 /var/www

# Expose port 80 for Apache
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
