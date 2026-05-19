#!/bin/bash
set -e

# Démarrer le service cron
service cron start

# Lancer Apache en premier plan
exec apache2-foreground
