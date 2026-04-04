#!/bin/bash
set -e

# Fixer les permissions du dossier uploads
mkdir -p /var/www/html/public/uploads
chown -R www-data:www-data /var/www/html/public/uploads
chmod -R 777 /var/www/html/public/uploads

# Exécuter les migrations
echo "[entrypoint] Exécution des migrations..."
php /var/www/html/database/migrate.php
echo "[entrypoint] Migrations terminées."

# Configurer les tâches cron
touch /var/log/app-cron.log
chown www-data:www-data /var/log/app-cron.log
# Exporter les variables d'environnement pour les scripts cron
{
    echo "DB_HOST=${DB_HOST}"
    echo "DB_PORT=${DB_PORT}"
    echo "DB_NAME=${DB_NAME}"
    echo "DB_USER=${DB_USER}"
    echo "DB_PASS=${DB_PASS}"
    echo "APP_URL=${APP_URL}"
    echo "APP_DEBUG=${APP_DEBUG:-false}"
    echo "SMTP_HOST=${SMTP_HOST}"
    echo "SMTP_PORT=${SMTP_PORT}"
    echo "SMTP_USER=${SMTP_USER}"
    echo "SMTP_PASS=${SMTP_PASS}"
    echo "SMTP_ENCRYPTION=${SMTP_ENCRYPTION}"
    echo "MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS}"
    echo "MAIL_FROM_NAME=${MAIL_FROM_NAME}"
    echo ""
    echo "# Récapitulatif journalier — tous les matins à 8h"
    echo "0 8 * * * /usr/local/bin/php /var/www/html/bin/daily-digest.php >> /var/log/app-cron.log 2>&1"
} > /tmp/app-cron
crontab -u www-data /tmp/app-cron
rm -f /tmp/app-cron
service cron start
echo "[entrypoint] Cron démarré."

# Démarrer Apache
exec apache2-foreground
