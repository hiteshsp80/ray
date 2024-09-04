# Use the official PHP image as a base image
FROM php:7.4-apache

# Update package list
RUN apt-get update && echo "apt-get update completed successfully"

# Install dependencies including oniguruma
RUN apt-get install -y \
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
    && echo "apt-get install dependencies completed successfully"

# Configure and install GD extension
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && echo "GD extension installed successfully"

# Install mbstring extension
RUN docker-php-ext-install -j$(nproc) mbstring \
    && echo "mbstring extension installed successfully"

# Install soap extension
RUN docker-php-ext-install -j$(nproc) soap \
    && echo "soap extension installed successfully"

# Install zip extension
RUN docker-php-ext-install -j$(nproc) zip \
    && echo "zip extension installed successfully"

# Install mysqli extension
RUN docker-php-ext-install -j$(nproc) mysqli \
    && echo "mysqli extension installed successfully"

# Install intl extension
RUN docker-php-ext-install -j$(nproc) intl \
    && echo "intl extension installed successfully"

# Enable Apache mod_rewrite
RUN a2enmod rewrite && echo "Apache mod_rewrite enabled successfully"

# Set the working directory
WORKDIR /var/www/html

# Download and extract Moodle 4.1
RUN curl -L https://download.moodle.org/download.php/direct/stable401/moodle-latest-401.tgz -o moodle.tgz \
    && tar -zxvf moodle.tgz \
    && mv moodle/* /var/www/html/ \
    && rm -rf moodle moodle.tgz \
    && chown -R www-data:www-data /var/www/html \
    && echo "Moodle 4.1 downloaded and extracted successfully"

# Create Moodle data directory and set correct ownership
RUN mkdir -p /var/www/moodledata && \
    chown -R www-data:www-data /var/www && \
    chmod -R 755 /var/www && \
    echo "Moodle data directory created and permissions set successfully"

# Expose port 80
EXPOSE 80

# Set the entry point
CMD ["apache2-foreground"]

