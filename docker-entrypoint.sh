#!/bin/bash
set -e

# Le volume ./uploads (bind mount) appartient à root sur l'hôte : donner l'accès à Apache
mkdir -p /var/www/html/uploads/logos /var/www/html/uploads/produits /var/www/html/uploads/utilisateurs
chown -R www-data:www-data /var/www/html/uploads
chmod -R 775 /var/www/html/uploads

# Démarrer le service cron
service cron start

# Lancer Apache en premier plan
exec apache2-foreground
