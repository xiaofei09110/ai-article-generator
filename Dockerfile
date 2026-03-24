FROM php:8.2-apache

# 安装必要的PHP扩展和系统依赖
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    default-mysql-client \
    && docker-php-ext-install curl pdo pdo_mysql mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 启用Apache模块
RUN a2enmod rewrite headers

# 设置PHP生产配置
COPY php.ini /usr/local/etc/php/php.ini

# 设置工作目录
WORKDIR /var/www/html

# 复制项目文件
COPY public/ /var/www/html/
COPY src/ /var/www/src/

# 设置权限
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/html \
    && chmod -R 750 /var/www/src

# 暴露端口
EXPOSE 80