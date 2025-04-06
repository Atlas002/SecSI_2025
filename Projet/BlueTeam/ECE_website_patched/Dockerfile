# Utiliser une image officielle de PHP avec Apache
FROM php:8.0-apache

# Définir le répertoire de travail
WORKDIR /var/www/html/

# Copier les fichiers de ton projet dans le conteneur
COPY . /var/www/html/

#Règmentation des fichiers php pour production
COPY config/php.ini /usr/local/etc/php/conf.d/custom.ini

# Activer les extensions PHP nécessaires (ex: mysqli, pdo_mysql si tu utilises une BDD)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Modifier les permissions
#RUN chown -R www-data:www-data /var/www/html/
RUN chown -R www-data:www-data /var/www/html/ && chmod -R 755 /var/www/html/

# Exposer le port 80 pour Apache
EXPOSE 80

