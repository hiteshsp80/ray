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
    nginx \
    supervisor \
    && apt-get clean && \
    echo "Dependencies installed successfully"

# # Configure and install GD extension
# RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
#     && docker-php-ext-install -j$(nproc) gd \
#     && echo "GD extension installed successfully"

# # Install mbstring extension
# RUN docker-php-ext-install -j$(nproc) mbstring \
#     && echo "mbstring extension installed successfully"

# # Install soap extension
# RUN docker-php-ext-install -j$(nproc) soap \
#     && echo "soap extension installed successfully"

# # Install zip extension
# RUN docker-php-ext-install -j$(nproc) zip \
#     && echo "zip extension installed successfully"

# # Install mysqli extension
# RUN docker-php-ext-install -j$(nproc) mysqli \
#     && echo "mysqli extension installed successfully"

# # Install intl extension
# RUN docker-php-ext-install -j$(nproc) intl \
#     && echo "intl extension installed successfully"

# # Enable Apache mod_rewrite
# RUN a2enmod rewrite && echo "Apache mod_rewrite enabled successfully"

# # Set the working directory
# WORKDIR /var/www/html

# # Download and extract Moodle
# RUN curl -L https://download.moodle.org/download.php/direct/stable39/moodle-latest-39.tgz -o moodle.tgz \
#     && tar -zxvf moodle.tgz \
#     && mv moodle/* /var/www/html/ \
#     && rm -rf moodle moodle.tgz \
#     && chown -R www-data:www-data /var/www/html \
#     && echo "Moodle downloaded and extracted successfully"

# # Expose port 80
# EXPOSE 80

# # Set the entry point
# CMD ["apache2-foreground"]

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd mbstring soap zip mysqli intl && \
    echo "PHP extensions installed successfully"

# Enable Apache mod_rewrite
RUN a2enmod rewrite && echo "Apache mod_rewrite enabled successfully"

# Create Supervisor configuration file
RUN echo "[supervisord] \n\
    nodaemon=true \n\
    [program:apache2] \n\
    command=/usr/sbin/apache2ctl -D FOREGROUND \n\
    [program:nginx] \n\
    command=/usr/sbin/nginx -g 'daemon off;'" > /etc/supervisor/conf.d/supervisord.conf

# Configure Nginx to proxy requests to Apache
RUN rm /etc/nginx/sites-enabled/default && \
    echo "server { \n\
    listen 80; \n\
    location / { \n\
    proxy_pass http://127.0.0.1:8080; \n\
    proxy_set_header Host \$host; \n\
    proxy_set_header X-Real-IP \$remote_addr; \n\
    proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for; \n\
    proxy_set_header X-Forwarded-Proto \$scheme; \n\
    } \n\
    }" > /etc/nginx/sites-available/default && \
    ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/

# Set the working directory
WORKDIR /var/www/html

# Download and extract Moodle
RUN curl -L https://download.moodle.org/download.php/direct/stable39/moodle-latest-39.tgz -o moodle.tgz && \
    tar -zxvf moodle.tgz && \
    mv moodle/* /var/www/html/ && \
    rm -rf moodle moodle.tgz && \
    chown -R www-data:www-data /var/www/html && \
    echo "Moodle downloaded and extracted successfully"

# Expose port 80 for Nginx
EXPOSE 80

# Use Supervisor to run both Apache and Nginx
CMD ["/usr/bin/supervisord"]


