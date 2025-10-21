FROM php:8.2-apache

# ติดตั้ง system dependencies สำหรับ GD Library และ extensions
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libonig-dev \
    zlib1g-dev \
    libzip-dev \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# ติดตั้ง PHP extensions ที่จำเป็น
RUN docker-php-ext-install -j$(nproc) \
    mysqli \
    pdo \
    pdo_mysql \
    mbstring \
    zip

# ติดตั้ง GD Library ด้วยการตั้งค่า paths
RUN docker-php-ext-configure gd --with-freetype=/usr/include/freetype2 --with-jpeg=/usr && \
    docker-php-ext-install -j$(nproc) gd

# ติดตั้ง Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# เปิด mod_rewrite
RUN a2enmod rewrite

# เปิด port 8080
EXPOSE 8080

# เปลี่ยน Apache ให้รันที่ port 8080
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# ตั้งค่า DocumentRoot ให้รองรับหลายโปรเจค (subfolder)
WORKDIR /var/www/html

# ปรับ PHP configuration สำหรับการอัปโหลดไฟล์ขนาดใหญ่
RUN echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/memory.ini && \
    echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/execution.ini && \
    echo "default_charset = \"UTF-8\"" >> /usr/local/etc/php/conf.d/charset.ini

# ตั้ง environment สำหรับ UTF-8 (Thai support)
ENV LANG=en_US.UTF-8 \
    LC_ALL=en_US.UTF-8 \
    LANGUAGE=en_US.UTF-8

