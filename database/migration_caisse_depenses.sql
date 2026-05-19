-- ============================================================================
-- MIGRATION : GESTION DE LA CAISSE ET DES DÉPENSES
-- ============================================================================
-- Exécuter ce script une seule fois pour ajouter les fonctionnalités :
--   1. Gestion de la caisse (sessions d'ouverture/fermeture + mouvements)
--   2. Catégories de dépenses
--   3. Enregistrement des dépenses (liées à la caisse disponible)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. TABLE : categories_depenses
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories_depenses` (
    `id_categorie`    INT AUTO_INCREMENT PRIMARY KEY,
    `nom_categorie`   VARCHAR(100) NOT NULL,
    `description`     TEXT NULL,
    `couleur`         VARCHAR(7) NOT NULL DEFAULT '#6c757d',
    `icone`           VARCHAR(50) NOT NULL DEFAULT 'receipt',
    `est_actif`       TINYINT(1) NOT NULL DEFAULT 1,
    `ordre_affichage` INT NOT NULL DEFAULT 0,
    `date_creation`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catégories par défaut
INSERT IGNORE INTO `categories_depenses`
    (`id_categorie`, `nom_categorie`, `description`, `couleur`, `icone`, `ordre_affichage`)
VALUES
    (1, 'Loyer & Charges',      'Loyer, eau, électricité, internet',               '#e74c3c', 'home',        1),
    (2, 'Salaires',             'Salaires et avances sur salaire',                 '#9b59b6', 'users',       2),
    (3, 'Approvisionnement',    'Achat de marchandises et matières premières',     '#3498db', 'package',     3),
    (4, 'Transport',            'Carburant, livraison, frais de déplacement',      '#f39c12', 'truck',       4),
    (5, 'Fournitures',          'Matériel de bureau et consommables',              '#1abc9c', 'tool',        5),
    (6, 'Entretien',            'Réparations et maintenance du local/équipements', '#e67e22', 'settings',    6),
    (7, 'Taxes & Impôts',       'Taxes, droits et charges fiscales',               '#c0392b', 'file-text',   7),
    (8, 'Divers',               'Autres dépenses non catégorisées',                '#95a5a6', 'more-horizontal', 8);

-- ----------------------------------------------------------------------------
-- 2. TABLE : caisse
-- Représente une session de caisse (ouverture → fermeture)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `caisse` (
    `id_caisse`        INT AUTO_INCREMENT PRIMARY KEY,
    `date_ouverture`   DATETIME NOT NULL,
    `date_fermeture`   DATETIME NULL,
    `solde_ouverture`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `solde_fermeture`  DECIMAL(15,2) NULL,
    `solde_theorique`  DECIMAL(15,2) NULL  COMMENT 'Calculé automatiquement à la fermeture',
    `ecart`            DECIMAL(15,2) NULL  COMMENT 'solde_fermeture - solde_theorique',
    `id_utilisateur`   INT NOT NULL,
    `statut`           ENUM('ouverte','fermee') NOT NULL DEFAULT 'ouverte',
    `notes`            TEXT NULL,
    `date_creation`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `date_modification` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_caisse_user` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs`(`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. TABLE : mouvements_caisse
-- Trace chaque entrée/sortie d'argent dans la caisse
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mouvements_caisse` (
    `id_mouvement`   INT AUTO_INCREMENT PRIMARY KEY,
    `id_caisse`      INT NOT NULL,
    `type_mouvement` ENUM('ouverture','vente_especes','remboursement_especes','entree_manuelle','depense','sortie_manuelle','fermeture') NOT NULL,
    `montant`        DECIMAL(15,2) NOT NULL COMMENT 'Toujours positif; le signe est donné par type_mouvement',
    `sens`           ENUM('entree','sortie') NOT NULL,
    `description`    VARCHAR(500) NULL,
    `reference`      VARCHAR(100) NULL COMMENT 'Ex: numéro de facture',
    `id_utilisateur` INT NOT NULL,
    `date_mouvement` DATETIME NOT NULL,
    `date_creation`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_mvt_caisse`  FOREIGN KEY (`id_caisse`)      REFERENCES `caisse`(`id_caisse`),
    CONSTRAINT `fk_mvt_user`    FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs`(`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS `idx_mvt_caisse_date` ON `mouvements_caisse`(`id_caisse`, `date_mouvement`);

-- ----------------------------------------------------------------------------
-- 4. TABLE : depenses
-- Dépenses enregistrées contre la caisse courante
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `depenses` (
    `id_depense`      INT AUTO_INCREMENT PRIMARY KEY,
    `id_categorie`    INT NOT NULL,
    `libelle`         VARCHAR(255) NOT NULL,
    `montant`         DECIMAL(15,2) NOT NULL,
    `id_caisse`       INT NULL COMMENT 'Caisse sur laquelle la dépense est imputée',
    `id_utilisateur`  INT NOT NULL,
    `notes`           TEXT NULL,
    `date_depense`    DATETIME NOT NULL,
    `statut`          ENUM('validee','annulee') NOT NULL DEFAULT 'validee',
    `date_creation`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `date_modification` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_dep_categorie` FOREIGN KEY (`id_categorie`)   REFERENCES `categories_depenses`(`id_categorie`),
    CONSTRAINT `fk_dep_caisse`    FOREIGN KEY (`id_caisse`)      REFERENCES `caisse`(`id_caisse`),
    CONSTRAINT `fk_dep_user`      FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs`(`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS `idx_dep_caisse`     ON `depenses`(`id_caisse`);
CREATE INDEX IF NOT EXISTS `idx_dep_categorie`  ON `depenses`(`id_categorie`);
CREATE INDEX IF NOT EXISTS `idx_dep_date`       ON `depenses`(`date_depense`);
