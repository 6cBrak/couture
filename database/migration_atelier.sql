-- ============================================================================
-- MIGRATION : MODULE ATELIER COUTURE
-- ============================================================================
-- À exécuter sur la base de données storesuite_tailor
-- ============================================================================

-- 1. Ajouter atelier_mode dans la table configuration
ALTER TABLE configuration ADD COLUMN IF NOT EXISTS atelier_mode TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Activer le module Atelier Couture';

-- 2. Table des tailleurs
CREATE TABLE IF NOT EXISTS tailleurs (
    id_tailleur     INT AUTO_INCREMENT PRIMARY KEY,
    nom             VARCHAR(150) NOT NULL,
    telephone       VARCHAR(30) DEFAULT NULL,
    specialite      VARCHAR(100) DEFAULT NULL,
    solde           DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Solde acomptes accumulés',
    notes           TEXT DEFAULT NULL,
    est_actif       TINYINT(1) NOT NULL DEFAULT 1,
    date_creation   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table des commandes atelier
CREATE TABLE IF NOT EXISTS commandes_atelier (
    id_commande     INT AUTO_INCREMENT PRIMARY KEY,
    reference       VARCHAR(30) NOT NULL UNIQUE,
    description     VARCHAR(255) NOT NULL,
    id_vente        INT DEFAULT NULL COMMENT 'Vente source (affectation)',
    id_client       INT DEFAULT NULL,
    id_tailleur     INT DEFAULT NULL,
    montant_total   DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    acompte_verse   DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Acompte payé par le client',
    acompte_tailleur DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Acompte à percevoir par le tailleur',
    statut          ENUM('recu','en_cours','terminee','livre','annulee') NOT NULL DEFAULT 'recu',
    date_commande   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_echeance   DATE DEFAULT NULL,
    notes           TEXT DEFAULT NULL,
    id_utilisateur  INT NOT NULL COMMENT 'Vendeur qui a enregistré la commande',
    date_mise_a_jour DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_vente)     REFERENCES ventes(id_vente)           ON DELETE SET NULL,
    FOREIGN KEY (id_client)    REFERENCES clients(id_client)         ON DELETE SET NULL,
    FOREIGN KEY (id_tailleur)  REFERENCES tailleurs(id_tailleur)     ON DELETE SET NULL,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Si la table existe déjà (migration partielle) : ajouter les colonnes manquantes
ALTER TABLE commandes_atelier ADD COLUMN IF NOT EXISTS id_vente INT DEFAULT NULL COMMENT 'Vente source (affectation)' AFTER reference;
ALTER TABLE commandes_atelier ADD COLUMN IF NOT EXISTS acompte_tailleur DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Acompte à percevoir par le tailleur' AFTER acompte_verse;
ALTER TABLE commandes_atelier ADD COLUMN IF NOT EXISTS id_paiement_tailleur INT DEFAULT NULL COMMENT 'ID paiement tailleur (commission payée)';
ALTER TABLE commandes_atelier ADD COLUMN IF NOT EXISTS id_detail_vente INT DEFAULT NULL COMMENT 'Ligne article (details_vente)';
ALTER TABLE commandes_atelier ADD COLUMN IF NOT EXISTS nom_produit VARCHAR(255) DEFAULT NULL COMMENT 'Nom article confié au tailleur';
ALTER TABLE commandes_atelier ADD COLUMN IF NOT EXISTS quantite INT NOT NULL DEFAULT 1 COMMENT 'Quantité confiée';
ALTER TABLE commandes_atelier ADD COLUMN IF NOT EXISTS numero_unite INT NOT NULL DEFAULT 1 COMMENT 'Numéro unité (1..quantite article)';

-- Type produit (standard avec stock / service sans stock)
ALTER TABLE produits ADD COLUMN IF NOT EXISTS type_produit ENUM('standard','service') NOT NULL DEFAULT 'standard' COMMENT 'standard=avec stock, service=sans stock';

-- 5. Colonnes confection dans la table ventes
ALTER TABLE ventes ADD COLUMN IF NOT EXISTS is_confection TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Commande de confection atelier';
ALTER TABLE ventes ADD COLUMN IF NOT EXISTS type_confection VARCHAR(100) DEFAULT NULL COMMENT 'Type de confection (chemise, pantalon, boubou...)';

-- 6. Table des paiements de commission tailleurs
CREATE TABLE IF NOT EXISTS paiements_tailleur (
    id_paiement     INT AUTO_INCREMENT PRIMARY KEY,
    id_tailleur     INT NOT NULL,
    montant         DECIMAL(15,2) NOT NULL,
    periode_debut   DATE NOT NULL,
    periode_fin     DATE NOT NULL,
    nb_commandes    INT NOT NULL DEFAULT 0,
    notes           TEXT DEFAULT NULL,
    id_utilisateur  INT NOT NULL,
    id_depense      INT DEFAULT NULL COMMENT 'ID dépense caisse associée',
    date_paiement   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_tailleur)    REFERENCES tailleurs(id_tailleur)      ON DELETE RESTRICT,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Table des mouvements de solde tailleur
CREATE TABLE IF NOT EXISTS mouvements_tailleur (
    id_mouvement    INT AUTO_INCREMENT PRIMARY KEY,
    id_tailleur     INT NOT NULL,
    type_mouvement  ENUM('credit','debit') NOT NULL,
    montant         DECIMAL(15,2) NOT NULL,
    description     VARCHAR(255) DEFAULT NULL,
    id_commande     INT DEFAULT NULL,
    id_utilisateur  INT NOT NULL,
    date_mouvement  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_tailleur)   REFERENCES tailleurs(id_tailleur)         ON DELETE CASCADE,
    FOREIGN KEY (id_commande)   REFERENCES commandes_atelier(id_commande) ON DELETE SET NULL,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
