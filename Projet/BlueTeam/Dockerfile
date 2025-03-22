# Utiliser une image officielle de PHP avec Apache
FROM php:8.0-apache

# Définir le répertoire de travail
WORKDIR /var/www/html/

# Copier les fichiers de ton projet dans le conteneur
COPY . /var/www/html/

# Activer les extensions PHP nécessaires (ex: mysqli, pdo_mysql si tu utilises une BDD)
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Modifier les permissions si nécessaire
RUN chown -R www-data:www-data /var/www/html/

# Exposer le port 80 pour Apache
EXPOSE 80

