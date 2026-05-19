#!/bin/bash
# ============================================================
#  StoreSuite Couture — Script d'installation
# ============================================================

set -e

# Couleurs
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
BLUE='\033[0;34m'; CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'

clear
echo -e "${BLUE}${BOLD}"
echo "  ╔══════════════════════════════════════════╗"
echo "  ║        StoreSuite Couture                ║"
echo "  ║        Script d'installation             ║"
echo "  ╚══════════════════════════════════════════╝"
echo -e "${NC}"

# ── Vérifications ──────────────────────────────────────────
echo -e "${CYAN}▶ Vérification des prérequis...${NC}"

if ! command -v docker &>/dev/null; then
    echo -e "${RED}✗ Docker n'est pas installé.${NC}"; exit 1
fi
if ! docker compose version &>/dev/null; then
    echo -e "${RED}✗ Docker Compose n'est pas disponible.${NC}"; exit 1
fi
if ! docker network inspect web &>/dev/null; then
    echo -e "${RED}✗ Le réseau Docker 'web' (Traefik) n'existe pas.${NC}"
    echo -e "  Crée-le avec : ${YELLOW}docker network create web${NC}"; exit 1
fi

echo -e "${GREEN}✓ Docker OK — réseau Traefik 'web' trouvé${NC}\n"

# ── Saisie de la configuration ─────────────────────────────
echo -e "${BOLD}Configuration de l'application${NC}"
echo -e "${YELLOW}─────────────────────────────────────────────${NC}\n"

# Domaine
while true; do
    read -rp "$(echo -e "  ${CYAN}Sous-domaine${NC} (ex: caisse.mondomaine.com) : ")" APP_DOMAIN
    [[ -n "$APP_DOMAIN" ]] && break
    echo -e "  ${RED}Le domaine est obligatoire.${NC}"
done

echo ""
echo -e "${BOLD}Base de données${NC}"
echo -e "${YELLOW}─────────────────────────────────────────────${NC}\n"

read -rp "$(echo -e "  ${CYAN}Nom de la base${NC} [couture_db] : ")" DB_NAME
DB_NAME=${DB_NAME:-couture_db}

read -rp "$(echo -e "  ${CYAN}Utilisateur MySQL${NC} [couture_user] : ")" DB_USER
DB_USER=${DB_USER:-couture_user}

while true; do
    read -srp "$(echo -e "  ${CYAN}Mot de passe MySQL${NC} : ")" DB_PASS; echo
    read -srp "$(echo -e "  ${CYAN}Confirmer le mot de passe${NC} : ")" DB_PASS2; echo
    [[ "$DB_PASS" == "$DB_PASS2" && -n "$DB_PASS" ]] && break
    echo -e "  ${RED}Les mots de passe ne correspondent pas ou sont vides.${NC}"
done

# Générer un mot de passe root aléatoire
DB_ROOT_PASS=$(openssl rand -base64 24 | tr -d '/+=' | head -c 32)
echo -e "  ${GREEN}✓ Mot de passe root MySQL généré automatiquement${NC}"

echo ""
echo -e "${BOLD}Email — Bilan journalier (optionnel)${NC}"
echo -e "${YELLOW}─────────────────────────────────────────────${NC}\n"
echo -e "  ${YELLOW}Laisser vide pour configurer plus tard depuis les paramètres.${NC}\n"

read -rp "$(echo -e "  ${CYAN}Email du chef (destinataire bilan)${NC} : ")" EMAIL_BILAN
read -rp "$(echo -e "  ${CYAN}Hôte SMTP${NC} (ex: mail.mondomaine.com) : ")" SMTP_HOST
read -rp "$(echo -e "  ${CYAN}Port SMTP${NC} [587] : ")" SMTP_PORT
SMTP_PORT=${SMTP_PORT:-587}
read -rp "$(echo -e "  ${CYAN}Utilisateur SMTP${NC} : ")" SMTP_USER
read -srp "$(echo -e "  ${CYAN}Mot de passe SMTP${NC} : ")" SMTP_PASS; echo

# ── Récapitulatif ──────────────────────────────────────────
echo ""
echo -e "${YELLOW}─────────────────────────────────────────────${NC}"
echo -e "${BOLD}Récapitulatif${NC}\n"
echo -e "  Domaine     : ${GREEN}https://${APP_DOMAIN}${NC}"
echo -e "  Base de données : ${GREEN}${DB_NAME}${NC} / ${GREEN}${DB_USER}${NC}"
[[ -n "$EMAIL_BILAN" ]] && echo -e "  Bilan email : ${GREEN}${EMAIL_BILAN}${NC}" || echo -e "  Bilan email : ${YELLOW}à configurer plus tard${NC}"
echo -e "${YELLOW}─────────────────────────────────────────────${NC}\n"

read -rp "$(echo -e "${BOLD}Confirmer et lancer l'installation ? [o/N]${NC} ")" CONFIRM
[[ "$CONFIRM" =~ ^[oOyY]$ ]] || { echo -e "${RED}Installation annulée.${NC}"; exit 0; }

# ── Création du .env ───────────────────────────────────────
echo ""
echo -e "${CYAN}▶ Création du fichier .env...${NC}"

cat > .env <<EOF
APP_DOMAIN=${APP_DOMAIN}

DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
DB_ROOT_PASS=${DB_ROOT_PASS}

# Email bilan journalier
EMAIL_BILAN=${EMAIL_BILAN}
SMTP_HOST=${SMTP_HOST}
SMTP_PORT=${SMTP_PORT}
SMTP_USER=${SMTP_USER}
SMTP_PASS=${SMTP_PASS}
EOF

echo -e "${GREEN}✓ .env créé${NC}"

# ── Mise à jour SMTP en base après démarrage ───────────────
# On insère les valeurs SMTP via SQL au premier démarrage

# ── Lancement Docker ───────────────────────────────────────
echo -e "${CYAN}▶ Construction et démarrage des conteneurs...${NC}\n"
docker compose up -d --build

# Attendre que la base soit prête
echo -e "\n${CYAN}▶ Attente de la base de données...${NC}"
attempt=0
until docker compose exec -T db mariadb-admin ping -h localhost -u root -p"${DB_ROOT_PASS}" --silent 2>/dev/null; do
    attempt=$((attempt+1))
    if [ $attempt -ge 30 ]; then
        echo -e "${RED}✗ La base de données ne répond pas après 30 tentatives.${NC}"
        docker compose logs db
        exit 1
    fi
    printf "."
    sleep 2
done
echo -e "\n${GREEN}✓ Base de données prête${NC}"

# Injecter la config SMTP en base si renseignée
if [[ -n "$SMTP_HOST" && -n "$SMTP_USER" ]]; then
    echo -e "${CYAN}▶ Configuration SMTP en base...${NC}"
    docker compose exec -T db mariadb -u"${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" <<SQL 2>/dev/null
UPDATE configuration SET
    smtp_host='${SMTP_HOST}',
    smtp_port=${SMTP_PORT},
    smtp_secure='tls',
    smtp_user='${SMTP_USER}',
    smtp_pass='${SMTP_PASS}',
    email_bilan='${EMAIL_BILAN}',
    bilan_actif=1
WHERE id_config=1;
SQL
    echo -e "${GREEN}✓ SMTP configuré${NC}"
fi

# ── Résumé final ───────────────────────────────────────────
echo ""
echo -e "${GREEN}${BOLD}"
echo "  ╔══════════════════════════════════════════╗"
echo "  ║       Installation terminée !            ║"
echo "  ╚══════════════════════════════════════════╝"
echo -e "${NC}"
echo -e "  🌐 Application : ${BOLD}https://${APP_DOMAIN}${NC}"
echo -e "  📦 Conteneurs  : $(docker compose ps --services | tr '\n' ' ')"
echo ""
echo -e "  Commandes utiles :"
echo -e "  ${CYAN}docker compose logs -f app${NC}   → voir les logs"
echo -e "  ${CYAN}docker compose restart app${NC}   → redémarrer"
echo -e "  ${CYAN}docker compose down${NC}           → arrêter"
echo ""
echo -e "${YELLOW}  ⚠ Le certificat SSL peut prendre 1-2 minutes.${NC}"
echo ""
