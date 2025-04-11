# Usa una imagen oficial de PHP con Apache
FROM php:8.1-apache

# Habilita extensiones necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copia el contenido de tu carpeta 'public' al directorio de Apache
COPY public/ /var/www/html/

# Da permisos
RUN chown -R www-data:www-data /var/www/html/

# Habilita el módulo de reescritura (opcional si usas .htaccess)
RUN a2enmod rewrite
