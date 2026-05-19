-- ============================================================================
-- MIGRATION : SYSTÈME COMPLET DE GESTION DE STOCK
-- ============================================================================
-- Date : 15 janvier 2026
-- Description : Ajout de la gestion multi-emplacements et traçabilité complète
-- Auteur : Fix iT Engineering
-- ============================================================================

-- ============================================================================
-- 1. TABLE FOURNISSEURS
-- ============================================================================
-- Gestion des fournisseurs pour tracer l'origine des produits
CREATE TABLE IF NOT EXISTS `fournisseurs` (
  `id_fournisseur` INT(11) NOT NULL AUTO_INCREMENT,
  `nom_fournisseur` VARCHAR(255) NOT NULL COMMENT 'Nom du fournisseur',
  `contact` VARCHAR(255) DEFAULT NULL COMMENT 'Personne de contact',
  `telephone` VARCHAR(50) DEFAULT NULL COMMENT 'Numéro de téléphone',
  `email` VARCHAR(255) DEFAULT NULL COMMENT 'Adresse email',
  `adresse` TEXT DEFAULT NULL COMMENT 'Adresse complète',
  `pays` VARCHAR(100) DEFAULT NULL COMMENT 'Pays',
  `ville` VARCHAR(100) DEFAULT NULL COMMENT 'Ville',
  `conditions_paiement` TEXT DEFAULT NULL COMMENT 'Conditions de paiement',
  `notes` TEXT DEFAULT NULL COMMENT 'Notes diverses',
  `est_actif` TINYINT(1) DEFAULT 1 COMMENT '0=Inactif, 1=Actif',
  `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `date_modification` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_fournisseur`),
  KEY `idx_nom_fournisseur` (`nom_fournisseur`),
  KEY `idx_est_actif` (`est_actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fournisseurs de produits';

-- Fournisseur par défaut
INSERT INTO `fournisseurs` (`id_fournisseur`, `nom_fournisseur`, `contact`, `est_actif`) 
VALUES (1, 'Fournisseur général', 'Divers', 1)
ON DUPLICATE KEY UPDATE `nom_fournisseur` = 'Fournisseur général';

-- ============================================================================
-- 2. TABLE DEPOTS (Emplacements de stockage)
-- ============================================================================
-- Gestion des différents emplacements : Magasin, Dépôts, Entrepôts, etc.
CREATE TABLE IF NOT EXISTS `depots` (
  `id_depot` INT(11) NOT NULL AUTO_INCREMENT,
  `nom_depot` VARCHAR(255) NOT NULL COMMENT 'Nom du dépôt/emplacement',
  `description` TEXT DEFAULT NULL COMMENT 'Description de l\'emplacement',
  `type_depot` ENUM('magasin','depot','entrepot','autre') DEFAULT 'depot' COMMENT 'Type d\'emplacement',
  `adresse` TEXT DEFAULT NULL COMMENT 'Adresse physique',
  `responsable` VARCHAR(255) DEFAULT NULL COMMENT 'Responsable du dépôt',
  `telephone` VARCHAR(50) DEFAULT NULL COMMENT 'Téléphone du dépôt',
  `capacite` INT(11) DEFAULT NULL COMMENT 'Capacité maximale (unités)',
  `est_principal` TINYINT(1) DEFAULT 0 COMMENT '1=Dépôt principal (Magasin)',
  `est_actif` TINYINT(1) DEFAULT 1 COMMENT '0=Inactif, 1=Actif',
  `ordre_affichage` INT(11) DEFAULT 0,
  `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `date_modification` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_depot`),
  UNIQUE KEY `unique_nom_depot` (`nom_depot`),
  KEY `idx_est_actif` (`est_actif`),
  KEY `idx_est_principal` (`est_principal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Emplacements de stockage';

-- Créer le dépôt principal "Magasin" (inchangeable)
INSERT INTO `depots` (`id_depot`, `nom_depot`, `description`, `type_depot`, `est_principal`, `est_actif`, `ordre_affichage`) 
VALUES (1, 'Magasin', 'Emplacement principal de vente (par défaut)', 'magasin', 1, 1, 1)
ON DUPLICATE KEY UPDATE `est_principal` = 1, `est_actif` = 1;

-- ============================================================================
-- 3. TABLE STOCK_PAR_DEPOT
-- ============================================================================
-- Gestion du stock par emplacement (Multi-localisation)
CREATE TABLE IF NOT EXISTS `stock_par_depot` (
  `id_stock` INT(11) NOT NULL AUTO_INCREMENT,
  `id_produit` INT(11) NOT NULL COMMENT 'Référence au produit',
  `id_depot` INT(11) NOT NULL COMMENT 'Référence au dépôt',
  `quantite` INT(11) NOT NULL DEFAULT 0 COMMENT 'Quantité dans ce dépôt',
  `seuil_alerte` INT(11) DEFAULT 10 COMMENT 'Seuil d\'alerte pour ce dépôt',
  `date_derniere_maj` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_stock`),
  UNIQUE KEY `unique_produit_depot` (`id_produit`, `id_depot`),
  KEY `idx_id_produit` (`id_produit`),
  KEY `idx_id_depot` (`id_depot`),
  KEY `idx_quantite` (`quantite`),
  CONSTRAINT `fk_stock_produit` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`) ON DELETE CASCADE,
  CONSTRAINT `fk_stock_depot` FOREIGN KEY (`id_depot`) REFERENCES `depots` (`id_depot`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stock par emplacement (multi-localisation)';

-- ============================================================================
-- 4. MODIFICATION TABLE MOUVEMENTS_STOCK
-- ============================================================================
-- Ajouter les colonnes pour la gestion complète des mouvements

-- Vérifier et ajouter id_depot_source
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'mouvements_stock' 
AND COLUMN_NAME = 'id_depot_source';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE `mouvements_stock` ADD COLUMN `id_depot_source` INT(11) DEFAULT NULL COMMENT ''Dépôt source (pour transferts)'' AFTER `id_produit`',
    'SELECT ''Column id_depot_source already exists'' AS msg');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier et ajouter id_depot_destination
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'mouvements_stock' 
AND COLUMN_NAME = 'id_depot_destination';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE `mouvements_stock` ADD COLUMN `id_depot_destination` INT(11) DEFAULT NULL COMMENT ''Dépôt destination'' AFTER `id_depot_source`',
    'SELECT ''Column id_depot_destination already exists'' AS msg');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier et ajouter id_fournisseur
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'mouvements_stock' 
AND COLUMN_NAME = 'id_fournisseur';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE `mouvements_stock` ADD COLUMN `id_fournisseur` INT(11) DEFAULT NULL COMMENT ''Fournisseur (pour entrées)'' AFTER `id_depot_destination`',
    'SELECT ''Column id_fournisseur already exists'' AS msg');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier et ajouter cout_unitaire
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'mouvements_stock' 
AND COLUMN_NAME = 'cout_unitaire';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE `mouvements_stock` ADD COLUMN `cout_unitaire` DECIMAL(15,2) DEFAULT NULL COMMENT ''Coût unitaire d\'\'achat'' AFTER `quantite_apres`',
    'SELECT ''Column cout_unitaire already exists'' AS msg');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier et ajouter cout_total
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'mouvements_stock' 
AND COLUMN_NAME = 'cout_total';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE `mouvements_stock` ADD COLUMN `cout_total` DECIMAL(15,2) DEFAULT NULL COMMENT ''Coût total du mouvement'' AFTER `cout_unitaire`',
    'SELECT ''Column cout_total already exists'' AS msg');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Modifier le type_mouvement pour ajouter nouveaux types
ALTER TABLE `mouvements_stock` 
MODIFY COLUMN `type_mouvement` ENUM('entree','sortie','ajustement','retour','transfert','inventaire','perte') NOT NULL;

-- Ajouter les index (ignorer si déjà existants)
SET @stmt = CONCAT('ALTER TABLE `mouvements_stock` ADD KEY `idx_depot_source` (`id_depot_source`)');
SET @stmt_prep = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='mouvements_stock' AND index_name='idx_depot_source') = 0, @stmt, 'SELECT "Index exists"');
PREPARE stmt FROM @stmt_prep;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @stmt = CONCAT('ALTER TABLE `mouvements_stock` ADD KEY `idx_depot_destination` (`id_depot_destination`)');
SET @stmt_prep = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='mouvements_stock' AND index_name='idx_depot_destination') = 0, @stmt, 'SELECT "Index exists"');
PREPARE stmt FROM @stmt_prep;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @stmt = CONCAT('ALTER TABLE `mouvements_stock` ADD KEY `idx_fournisseur` (`id_fournisseur`)');
SET @stmt_prep = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='mouvements_stock' AND index_name='idx_fournisseur') = 0, @stmt, 'SELECT "Index exists"');
PREPARE stmt FROM @stmt_prep;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @stmt = CONCAT('ALTER TABLE `mouvements_stock` ADD KEY `idx_type_mouvement` (`type_mouvement`)');
SET @stmt_prep = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='mouvements_stock' AND index_name='idx_type_mouvement') = 0, @stmt, 'SELECT "Index exists"');
PREPARE stmt FROM @stmt_prep;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @stmt = CONCAT('ALTER TABLE `mouvements_stock` ADD KEY `idx_date_mouvement` (`date_mouvement`)');
SET @stmt_prep = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='mouvements_stock' AND index_name='idx_date_mouvement') = 0, @stmt, 'SELECT "Index exists"');
PREPARE stmt FROM @stmt_prep;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- 5. MODIFICATION TABLE PRODUITS
-- ============================================================================
-- Ajouter le fournisseur principal

-- Vérifier et ajouter id_fournisseur_principal
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'produits' 
AND COLUMN_NAME = 'id_fournisseur_principal';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE `produits` ADD COLUMN `id_fournisseur_principal` INT(11) DEFAULT NULL COMMENT ''Fournisseur principal du produit'' AFTER `id_categorie`',
    'SELECT ''Column id_fournisseur_principal already exists'' AS msg');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter l'index
SET @stmt = CONCAT('ALTER TABLE `produits` ADD KEY `idx_fournisseur_principal` (`id_fournisseur_principal`)');
SET @stmt_prep = IF((SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='produits' AND index_name='idx_fournisseur_principal') = 0, @stmt, 'SELECT "Index exists"');
PREPARE stmt FROM @stmt_prep;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- 6. VUE : STOCK GLOBAL PAR PRODUIT
-- ============================================================================
-- Vue pour calculer le stock total de chaque produit (somme de tous les dépôts)
CREATE OR REPLACE VIEW `vue_stock_global` AS
SELECT 
    p.id_produit,
    p.code_produit,
    p.nom_produit,
    p.id_categorie,
    c.nom_categorie,
    p.prix_achat,
    p.prix_vente,
    COALESCE(SUM(spd.quantite), 0) AS stock_total,
    p.seuil_alerte,
    p.seuil_critique,
    p.unite_mesure,
    p.est_actif,
    CASE 
        WHEN COALESCE(SUM(spd.quantite), 0) = 0 THEN 'rupture'
        WHEN COALESCE(SUM(spd.quantite), 0) <= p.seuil_critique THEN 'critique'
        WHEN COALESCE(SUM(spd.quantite), 0) <= p.seuil_alerte THEN 'alerte'
        ELSE 'normal'
    END AS statut_stock
FROM produits p
LEFT JOIN categories c ON p.id_categorie = c.id_categorie
LEFT JOIN stock_par_depot spd ON p.id_produit = spd.id_produit
WHERE p.est_actif = 1
GROUP BY p.id_produit, p.code_produit, p.nom_produit, p.id_categorie, 
         c.nom_categorie, p.prix_achat, p.prix_vente, p.seuil_alerte, 
         p.seuil_critique, p.unite_mesure, p.est_actif;

-- ============================================================================
-- 7. VUE : INVENTAIRE COMPLET (Stock par dépôt)
-- ============================================================================
CREATE OR REPLACE VIEW `vue_inventaire_complet` AS
SELECT 
    p.id_produit,
    p.code_produit,
    p.nom_produit,
    c.nom_categorie,
    d.id_depot,
    d.nom_depot,
    d.type_depot,
    spd.quantite,
    p.prix_achat,
    p.prix_vente,
    (spd.quantite * p.prix_achat) AS valeur_stock_achat,
    (spd.quantite * p.prix_vente) AS valeur_stock_vente,
    p.unite_mesure,
    spd.seuil_alerte,
    CASE 
        WHEN spd.quantite = 0 THEN 'rupture'
        WHEN spd.quantite <= spd.seuil_alerte THEN 'alerte'
        ELSE 'normal'
    END AS statut_depot
FROM produits p
INNER JOIN stock_par_depot spd ON p.id_produit = spd.id_produit
INNER JOIN depots d ON spd.id_depot = d.id_depot
LEFT JOIN categories c ON p.id_categorie = c.id_categorie
WHERE p.est_actif = 1 AND d.est_actif = 1
ORDER BY p.nom_produit, d.ordre_affichage, d.nom_depot;

-- ============================================================================
-- 8. VUE : HISTORIQUE MOUVEMENTS (Détaillé)
-- ============================================================================
CREATE OR REPLACE VIEW `vue_mouvements_stock_detail` AS
SELECT 
    m.id_mouvement,
    m.type_mouvement,
    m.date_mouvement,
    p.id_produit,
    p.code_produit,
    p.nom_produit,
    p.unite_mesure,
    m.quantite,
    m.quantite_avant,
    m.quantite_apres,
    ds.nom_depot AS depot_source,
    dd.nom_depot AS depot_destination,
    f.nom_fournisseur,
    m.cout_unitaire,
    m.cout_total,
    m.motif,
    m.notes,
    u.nom_complet AS utilisateur,
    v.numero_facture,
    CASE m.type_mouvement
        WHEN 'entree' THEN 'Entrée stock'
        WHEN 'sortie' THEN 'Sortie (Vente)'
        WHEN 'transfert' THEN 'Transfert'
        WHEN 'ajustement' THEN 'Ajustement'
        WHEN 'inventaire' THEN 'Inventaire'
        WHEN 'perte' THEN 'Perte/Casse'
        WHEN 'retour' THEN 'Retour'
        ELSE 'Autre'
    END AS type_mouvement_libelle
FROM mouvements_stock m
INNER JOIN produits p ON m.id_produit = p.id_produit
LEFT JOIN depots ds ON m.id_depot_source = ds.id_depot
LEFT JOIN depots dd ON m.id_depot_destination = dd.id_depot
LEFT JOIN fournisseurs f ON m.id_fournisseur = f.id_fournisseur
LEFT JOIN utilisateurs u ON m.id_utilisateur = u.id_utilisateur
LEFT JOIN ventes v ON m.id_vente = v.id_vente
ORDER BY m.date_mouvement DESC, m.id_mouvement DESC;

-- ============================================================================
-- 9. MIGRATION DES DONNÉES EXISTANTES
-- ============================================================================
-- Migrer le stock existant des produits vers stock_par_depot (Magasin)
INSERT INTO stock_par_depot (id_produit, id_depot, quantite, seuil_alerte)
SELECT 
    id_produit,
    1 AS id_depot, -- Magasin par défaut
    quantite_stock,
    seuil_alerte
FROM produits
WHERE id_produit NOT IN (SELECT id_produit FROM stock_par_depot WHERE id_depot = 1)
ON DUPLICATE KEY UPDATE 
    quantite = VALUES(quantite),
    seuil_alerte = VALUES(seuil_alerte);

-- ============================================================================
-- 10. TRIGGERS : SYNCHRONISATION AUTOMATIQUE
-- ============================================================================

-- Trigger : Mettre à jour quantite_stock dans produits après modification stock_par_depot
DELIMITER $$

DROP TRIGGER IF EXISTS `after_stock_par_depot_update`$$
CREATE TRIGGER `after_stock_par_depot_update`
AFTER UPDATE ON `stock_par_depot`
FOR EACH ROW
BEGIN
    -- Recalculer le stock total du produit
    UPDATE produits 
    SET quantite_stock = (
        SELECT COALESCE(SUM(quantite), 0) 
        FROM stock_par_depot 
        WHERE id_produit = NEW.id_produit
    )
    WHERE id_produit = NEW.id_produit;
END$$

DROP TRIGGER IF EXISTS `after_stock_par_depot_insert`$$
CREATE TRIGGER `after_stock_par_depot_insert`
AFTER INSERT ON `stock_par_depot`
FOR EACH ROW
BEGIN
    -- Recalculer le stock total du produit
    UPDATE produits 
    SET quantite_stock = (
        SELECT COALESCE(SUM(quantite), 0) 
        FROM stock_par_depot 
        WHERE id_produit = NEW.id_produit
    )
    WHERE id_produit = NEW.id_produit;
END$$

DROP TRIGGER IF EXISTS `after_stock_par_depot_delete`$$
CREATE TRIGGER `after_stock_par_depot_delete`
AFTER DELETE ON `stock_par_depot`
FOR EACH ROW
BEGIN
    -- Recalculer le stock total du produit
    UPDATE produits 
    SET quantite_stock = (
        SELECT COALESCE(SUM(quantite), 0) 
        FROM stock_par_depot 
        WHERE id_produit = OLD.id_produit
    )
    WHERE id_produit = OLD.id_produit;
END$$

DELIMITER ;

-- ============================================================================
-- FIN DE LA MIGRATION
-- ============================================================================
-- Toutes les tables, vues, triggers et données ont été créés/modifiés
-- Le système est maintenant prêt pour la gestion multi-emplacements
-- ============================================================================
