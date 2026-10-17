-- MariaDB dump 10.19  Distrib 10.4.28-MariaDB, for Win64 (AMD64)
--
-- Schéma vierge StoreSuite (aucune donnée client)
-- ------------------------------------------------------
-- Server version	10.4.28-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `caisse`
--

DROP TABLE IF EXISTS `caisse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `caisse` (
  `id_caisse` int(11) NOT NULL AUTO_INCREMENT,
  `date_ouverture` datetime NOT NULL,
  `date_fermeture` datetime DEFAULT NULL,
  `solde_ouverture` decimal(15,2) NOT NULL DEFAULT 0.00,
  `solde_fermeture` decimal(15,2) DEFAULT NULL,
  `solde_theorique` decimal(15,2) DEFAULT NULL COMMENT 'Calculé automatiquement à la fermeture',
  `ecart` decimal(15,2) DEFAULT NULL COMMENT 'solde_fermeture - solde_theorique',
  `id_utilisateur` int(11) NOT NULL,
  `statut` enum('ouverte','fermee') NOT NULL DEFAULT 'ouverte',
  `notes` text DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_caisse`),
  KEY `fk_caisse_user` (`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caisse`
--

LOCK TABLES `caisse` WRITE;
/*!40000 ALTER TABLE `caisse` DISABLE KEYS */;
/*!40000 ALTER TABLE `caisse` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id_categorie` int(11) NOT NULL AUTO_INCREMENT,
  `nom_categorie` varchar(255) NOT NULL COMMENT 'Nom de la catégorie',
  `description` text DEFAULT NULL COMMENT 'Description de la catégorie',
  `icone` varchar(100) DEFAULT NULL COMMENT 'Icône ou classe CSS',
  `couleur` varchar(7) DEFAULT NULL COMMENT 'Couleur associée (format HEX)',
  `ordre_affichage` int(11) DEFAULT 0 COMMENT 'Ordre d''affichage',
  `est_actif` tinyint(1) DEFAULT 1 COMMENT '0=Inactif, 1=Actif',
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_categorie`),
  KEY `idx_actif` (`est_actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catégories de produits';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories_depenses`
--

DROP TABLE IF EXISTS `categories_depenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories_depenses` (
  `id_categorie` int(11) NOT NULL AUTO_INCREMENT,
  `nom_categorie` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `couleur` varchar(7) NOT NULL DEFAULT '#6c757d',
  `icone` varchar(50) NOT NULL DEFAULT 'receipt',
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `ordre_affichage` int(11) NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_categorie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories_depenses`
--

LOCK TABLES `categories_depenses` WRITE;
/*!40000 ALTER TABLE `categories_depenses` DISABLE KEYS */;
INSERT INTO `categories_depenses` VALUES (1,'Loyer & Charges','Loyer, eau, électricité, internet','#e74c3c','home',1,1,'2026-03-26 06:54:17'),(2,'Salaires','Salaires et avances sur salaire','#9b59b6','users',1,2,'2026-03-26 06:54:17'),(3,'Approvisionnement','Achat de marchandises et matières premières','#3498db','package',1,3,'2026-03-26 06:54:17'),(4,'Transport','Carburant, livraison, frais de déplacement','#f39c12','truck',1,4,'2026-03-26 06:54:17'),(5,'Fournitures','Matériel de bureau et consommables','#1abc9c','tool',1,5,'2026-03-26 06:54:17'),(6,'Entretien','Réparations et maintenance du local/équipements','#e67e22','settings',1,6,'2026-03-26 06:54:17'),(7,'Taxes & Impôts','Taxes, droits et charges fiscales','#c0392b','file-text',1,7,'2026-03-26 06:54:17'),(8,'Divers','Autres dépenses non catégorisées','#95a5a6','more-horizontal',1,8,'2026-03-26 06:54:17'),(9,'Atelier Couture','Commissions et paiements tailleurs','#8b5cf6','scissors',1,99,'2026-04-01 17:16:21');
/*!40000 ALTER TABLE `categories_depenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients` (
  `id_client` int(11) NOT NULL AUTO_INCREMENT,
  `nom_client` varchar(255) NOT NULL COMMENT 'Nom du client',
  `telephone` varchar(50) DEFAULT NULL COMMENT 'Téléphone',
  `email` varchar(255) DEFAULT NULL COMMENT 'Email',
  `adresse` text DEFAULT NULL COMMENT 'Adresse complète',
  `type_client` enum('particulier','entreprise') DEFAULT 'particulier',
  `numero_fiscal` varchar(100) DEFAULT NULL COMMENT 'Numéro fiscal (pour entreprises)',
  `total_achats` decimal(15,2) DEFAULT 0.00 COMMENT 'Total des achats',
  `nombre_achats` int(11) DEFAULT 0 COMMENT 'Nombre d''achats',
  `date_dernier_achat` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL COMMENT 'Notes sur le client',
  `est_actif` tinyint(1) DEFAULT 1,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_client`),
  KEY `idx_telephone` (`telephone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Base de données clients';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` (`nom_client`, `type_client`) VALUES ('Client Comptoir','particulier');
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `commandes_atelier`
--

DROP TABLE IF EXISTS `commandes_atelier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `commandes_atelier` (
  `id_commande` int(11) NOT NULL AUTO_INCREMENT,
  `reference` varchar(30) NOT NULL,
  `description` varchar(255) NOT NULL,
  `id_vente` int(11) DEFAULT NULL COMMENT 'Vente source (affectation)',
  `id_client` int(11) DEFAULT NULL,
  `id_tailleur` int(11) DEFAULT NULL,
  `montant_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `acompte_verse` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Acompte payé par le client',
  `acompte_tailleur` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Acompte à percevoir par le tailleur',
  `statut` enum('recu','en_cours','terminee','livre','annulee') NOT NULL DEFAULT 'recu',
  `date_commande` datetime NOT NULL DEFAULT current_timestamp(),
  `date_echeance` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `id_utilisateur` int(11) NOT NULL COMMENT 'Vendeur qui a enregistré la commande',
  `date_mise_a_jour` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `id_paiement_tailleur` int(11) DEFAULT NULL COMMENT 'ID paiement tailleur (commission payée)',
  `id_detail_vente` int(11) DEFAULT NULL,
  `nom_produit` varchar(255) DEFAULT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `numero_unite` int(11) NOT NULL DEFAULT 1 COMMENT 'Numéro unité (1..quantite article)',
  PRIMARY KEY (`id_commande`),
  UNIQUE KEY `reference` (`reference`),
  KEY `id_client` (`id_client`),
  KEY `id_tailleur` (`id_tailleur`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `commandes_atelier`
--

LOCK TABLES `commandes_atelier` WRITE;
/*!40000 ALTER TABLE `commandes_atelier` DISABLE KEYS */;
/*!40000 ALTER TABLE `commandes_atelier` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuration`
--

DROP TABLE IF EXISTS `configuration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configuration` (
  `id_config` int(11) NOT NULL AUTO_INCREMENT,
  `nom_boutique` varchar(255) NOT NULL COMMENT 'Nom de la boutique/entreprise',
  `slogan` varchar(255) DEFAULT NULL COMMENT 'Slogan ou description courte',
  `logo` varchar(255) DEFAULT NULL COMMENT 'Chemin vers le fichier logo',
  `couleur_primaire` varchar(7) DEFAULT '#e6e64c' COMMENT 'Couleur principale (format HEX)',
  `couleur_secondaire` varchar(7) DEFAULT '#556a94' COMMENT 'Couleur secondaire (format HEX)',
  `adresse` text DEFAULT NULL COMMENT 'Adresse complète de l''entreprise',
  `telephone` varchar(100) DEFAULT NULL COMMENT 'Numéro(s) de téléphone',
  `email` varchar(255) DEFAULT NULL COMMENT 'Adresse email',
  `site_web` varchar(255) DEFAULT NULL COMMENT 'Site web de l''entreprise',
  `num_registre_commerce` varchar(100) DEFAULT NULL COMMENT 'Numéro d''enregistrement (RCCM, etc.)',
  `num_impot` varchar(100) DEFAULT NULL COMMENT 'Numéro fiscal/TVA',
  `devise` varchar(10) DEFAULT '$' COMMENT 'Symbole de la devise utilisée',
  `taux_tva` decimal(5,2) DEFAULT 0.00 COMMENT 'Taux de TVA par défaut (%)',
  `fuseau_horaire` varchar(50) DEFAULT 'Africa/Lubumbashi' COMMENT 'Fuseau horaire',
  `langue` varchar(10) DEFAULT 'fr' COMMENT 'Langue du système (fr, en, etc.)',
  `est_configure` tinyint(1) DEFAULT 0 COMMENT '0=Non configuré, 1=Configuré',
  `date_configuration` datetime DEFAULT NULL COMMENT 'Date de première configuration',
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `atelier_mode` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Activer le module Atelier Couture',
  `smtp_host` varchar(255) DEFAULT '' COMMENT 'H',
  `smtp_port` smallint(6) DEFAULT 587 COMMENT 'Port SMTP',
  `smtp_secure` varchar(10) DEFAULT 'tls' COMMENT 'tls ou ssl',
  `smtp_user` varchar(255) DEFAULT '' COMMENT 'Utilisateur SMTP',
  `smtp_pass` varchar(255) DEFAULT '' COMMENT 'Mot de passe SMTP',
  `smtp_from` varchar(255) DEFAULT '' COMMENT 'Email exp',
  `smtp_from_name` varchar(255) DEFAULT '' COMMENT 'Nom exp',
  `email_bilan` varchar(255) DEFAULT '' COMMENT 'Email destinataire bilan',
  `bilan_actif` tinyint(1) DEFAULT 0 COMMENT 'Activer envoi bilan journalier',
  PRIMARY KEY (`id_config`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Paramètres globaux du système';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuration`
--

LOCK TABLES `configuration` WRITE;
/*!40000 ALTER TABLE `configuration` DISABLE KEYS */;
INSERT INTO `configuration` (`id_config`,`nom_boutique`,`couleur_primaire`,`couleur_secondaire`,`devise`,`taux_tva`,`fuseau_horaire`,`langue`,`est_configure`,`atelier_mode`) VALUES (1,'','#0066cc','#004d99','XOF',0.00,'Africa/Ouagadougou','fr',0,1);
/*!40000 ALTER TABLE `configuration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `depenses`
--

DROP TABLE IF EXISTS `depenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `depenses` (
  `id_depense` int(11) NOT NULL AUTO_INCREMENT,
  `id_categorie` int(11) NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `id_caisse` int(11) DEFAULT NULL COMMENT 'Caisse sur laquelle la dépense est imputée',
  `id_utilisateur` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `date_depense` datetime NOT NULL,
  `statut` enum('validee','annulee') NOT NULL DEFAULT 'validee',
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_depense`),
  KEY `fk_dep_user` (`id_utilisateur`),
  KEY `idx_dep_caisse` (`id_caisse`),
  KEY `idx_dep_categorie` (`id_categorie`),
  KEY `idx_dep_date` (`date_depense`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `depenses`
--

LOCK TABLES `depenses` WRITE;
/*!40000 ALTER TABLE `depenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `depenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `depots`
--

DROP TABLE IF EXISTS `depots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `depots` (
  `id_depot` int(11) NOT NULL AUTO_INCREMENT,
  `nom_depot` varchar(255) NOT NULL COMMENT 'Nom du dépôt/emplacement',
  `description` text DEFAULT NULL COMMENT 'Description de l''emplacement',
  `type_depot` enum('magasin','depot','entrepot','autre') DEFAULT 'depot' COMMENT 'Type d''emplacement',
  `adresse` text DEFAULT NULL COMMENT 'Adresse physique',
  `responsable` varchar(255) DEFAULT NULL COMMENT 'Responsable du dépôt',
  `telephone` varchar(50) DEFAULT NULL COMMENT 'Téléphone du dépôt',
  `capacite` int(11) DEFAULT NULL COMMENT 'Capacité maximale (unités)',
  `est_principal` tinyint(1) DEFAULT 0 COMMENT '1=Dépôt principal (Magasin)',
  `est_actif` tinyint(1) DEFAULT 1 COMMENT '0=Inactif, 1=Actif',
  `ordre_affichage` int(11) DEFAULT 0,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_depot`),
  UNIQUE KEY `unique_nom_depot` (`nom_depot`),
  KEY `idx_est_actif` (`est_actif`),
  KEY `idx_est_principal` (`est_principal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Emplacements de stockage';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `depots`
--

LOCK TABLES `depots` WRITE;
/*!40000 ALTER TABLE `depots` DISABLE KEYS */;
INSERT INTO `depots` VALUES (1,'Magasin','Emplacement principal de vente (par défaut)','magasin',NULL,NULL,NULL,NULL,1,1,1,'2026-03-10 22:35:51','2026-03-10 22:35:51');
/*!40000 ALTER TABLE `depots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `details_vente`
--

DROP TABLE IF EXISTS `details_vente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `details_vente` (
  `id_detail` int(11) NOT NULL AUTO_INCREMENT,
  `id_vente` int(11) NOT NULL COMMENT 'Référence à la vente',
  `id_produit` int(11) NOT NULL COMMENT 'Produit vendu',
  `nom_produit` varchar(255) NOT NULL COMMENT 'Nom du produit',
  `quantite` int(11) NOT NULL DEFAULT 1 COMMENT 'Quantité vendue',
  `prix_unitaire` decimal(15,2) NOT NULL COMMENT 'Prix unitaire',
  `prix_achat_unitaire` decimal(15,2) NOT NULL COMMENT 'Prix achat',
  `prix_total` decimal(15,2) NOT NULL COMMENT 'Total ligne',
  `benefice_ligne` decimal(15,2) NOT NULL COMMENT 'Bénéfice ligne',
  `remise_ligne` decimal(15,2) DEFAULT 0.00 COMMENT 'Remise ligne',
  `date_creation` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_detail`),
  KEY `idx_vente` (`id_vente`),
  KEY `idx_produit` (`id_produit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `details_vente`
--

LOCK TABLES `details_vente` WRITE;
/*!40000 ALTER TABLE `details_vente` DISABLE KEYS */;
/*!40000 ALTER TABLE `details_vente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fournisseurs`
--

DROP TABLE IF EXISTS `fournisseurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fournisseurs` (
  `id_fournisseur` int(11) NOT NULL AUTO_INCREMENT,
  `nom_fournisseur` varchar(255) NOT NULL COMMENT 'Nom du fournisseur',
  `contact` varchar(255) DEFAULT NULL COMMENT 'Personne de contact',
  `telephone` varchar(50) DEFAULT NULL COMMENT 'Numéro de téléphone',
  `email` varchar(255) DEFAULT NULL COMMENT 'Adresse email',
  `adresse` text DEFAULT NULL COMMENT 'Adresse complète',
  `pays` varchar(100) DEFAULT NULL COMMENT 'Pays',
  `ville` varchar(100) DEFAULT NULL COMMENT 'Ville',
  `conditions_paiement` text DEFAULT NULL COMMENT 'Conditions de paiement',
  `notes` text DEFAULT NULL COMMENT 'Notes diverses',
  `est_actif` tinyint(1) DEFAULT 1 COMMENT '0=Inactif, 1=Actif',
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_fournisseur`),
  KEY `idx_nom_fournisseur` (`nom_fournisseur`),
  KEY `idx_est_actif` (`est_actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fournisseurs de produits';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fournisseurs`
--

LOCK TABLES `fournisseurs` WRITE;
/*!40000 ALTER TABLE `fournisseurs` DISABLE KEYS */;
INSERT INTO `fournisseurs` VALUES (1,'Fournisseur général','Divers',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-01-15 09:11:04','2026-01-15 07:11:04');
/*!40000 ALTER TABLE `fournisseurs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `logs_activites`
--

DROP TABLE IF EXISTS `logs_activites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs_activites` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) DEFAULT NULL,
  `type_action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type d''action (connexion, vente, modification, etc.)',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Description détaillée de l''action',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Adresse IP',
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Navigateur/Device',
  `donnees_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Données supplémentaires en JSON',
  `date_action` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_log`),
  KEY `idx_utilisateur` (`id_utilisateur`),
  KEY `idx_type` (`type_action`),
  KEY `idx_date` (`date_action`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `logs_activites`
--

LOCK TABLES `logs_activites` WRITE;
/*!40000 ALTER TABLE `logs_activites` DISABLE KEYS */;
/*!40000 ALTER TABLE `logs_activites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mouvements`
--

DROP TABLE IF EXISTS `mouvements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mouvements` (
  `id_mouvement` int(11) NOT NULL AUTO_INCREMENT,
  `id_produit` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `type_mouvement` enum('entree','sortie','ajustement','vente') NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(15,2) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL COMMENT 'Numéro facture ou bon',
  `motif` text DEFAULT NULL,
  `date_mouvement` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_mouvement`),
  KEY `idx_produit` (`id_produit`),
  KEY `idx_utilisateur` (`id_utilisateur`),
  KEY `idx_date` (`date_mouvement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mouvements`
--

LOCK TABLES `mouvements` WRITE;
/*!40000 ALTER TABLE `mouvements` DISABLE KEYS */;
/*!40000 ALTER TABLE `mouvements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mouvements_caisse`
--

DROP TABLE IF EXISTS `mouvements_caisse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mouvements_caisse` (
  `id_mouvement` int(11) NOT NULL AUTO_INCREMENT,
  `id_caisse` int(11) NOT NULL,
  `type_mouvement` enum('ouverture','vente_especes','remboursement_especes','entree_manuelle','depense','sortie_manuelle','fermeture') NOT NULL,
  `montant` decimal(15,2) NOT NULL COMMENT 'Toujours positif; le signe est donné par type_mouvement',
  `sens` enum('entree','sortie') NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL COMMENT 'Ex: numéro de facture',
  `id_utilisateur` int(11) NOT NULL,
  `date_mouvement` datetime NOT NULL,
  `date_creation` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_mouvement`),
  KEY `fk_mvt_user` (`id_utilisateur`),
  KEY `idx_mvt_caisse_date` (`id_caisse`,`date_mouvement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mouvements_caisse`
--

LOCK TABLES `mouvements_caisse` WRITE;
/*!40000 ALTER TABLE `mouvements_caisse` DISABLE KEYS */;
/*!40000 ALTER TABLE `mouvements_caisse` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mouvements_stock`
--

DROP TABLE IF EXISTS `mouvements_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mouvements_stock` (
  `id_mouvement` int(11) NOT NULL AUTO_INCREMENT,
  `id_produit` int(11) NOT NULL COMMENT 'Produit concerné',
  `id_depot_source` int(11) DEFAULT NULL COMMENT 'Dépôt source (pour transferts)',
  `id_depot_destination` int(11) DEFAULT NULL COMMENT 'Dépôt destination',
  `id_fournisseur` int(11) DEFAULT NULL COMMENT 'Fournisseur (pour entrées)',
  `type_mouvement` enum('entree','sortie','ajustement','retour','transfert','inventaire','perte') NOT NULL,
  `quantite` int(11) NOT NULL COMMENT 'Quantité du mouvement',
  `quantite_avant` int(11) NOT NULL COMMENT 'Stock avant le mouvement',
  `quantite_apres` int(11) NOT NULL COMMENT 'Stock après le mouvement',
  `cout_unitaire` decimal(15,2) DEFAULT NULL COMMENT 'Coût unitaire d''achat',
  `cout_total` decimal(15,2) DEFAULT NULL COMMENT 'Coût total du mouvement',
  `id_vente` int(11) DEFAULT NULL COMMENT 'Référence vente si sortie',
  `id_utilisateur` int(11) NOT NULL COMMENT 'Utilisateur qui a fait l''opération',
  `motif` varchar(255) DEFAULT NULL COMMENT 'Raison du mouvement',
  `notes` text DEFAULT NULL,
  `date_mouvement` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_mouvement`),
  KEY `idx_produit` (`id_produit`),
  KEY `idx_type` (`type_mouvement`),
  KEY `idx_date` (`date_mouvement`),
  KEY `fk_mouvement_utilisateur` (`id_utilisateur`),
  KEY `idx_depot_source` (`id_depot_source`),
  KEY `idx_depot_destination` (`id_depot_destination`),
  KEY `idx_fournisseur` (`id_fournisseur`),
  KEY `idx_type_mouvement` (`type_mouvement`),
  KEY `idx_date_mouvement` (`date_mouvement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des mouvements de stock';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mouvements_stock`
--

LOCK TABLES `mouvements_stock` WRITE;
/*!40000 ALTER TABLE `mouvements_stock` DISABLE KEYS */;
/*!40000 ALTER TABLE `mouvements_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mouvements_tailleur`
--

DROP TABLE IF EXISTS `mouvements_tailleur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mouvements_tailleur` (
  `id_mouvement` int(11) NOT NULL AUTO_INCREMENT,
  `id_tailleur` int(11) NOT NULL,
  `type_mouvement` enum('credit','debit') NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `id_commande` int(11) DEFAULT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `date_mouvement` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_mouvement`),
  KEY `id_tailleur` (`id_tailleur`),
  KEY `id_commande` (`id_commande`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mouvements_tailleur`
--

LOCK TABLES `mouvements_tailleur` WRITE;
/*!40000 ALTER TABLE `mouvements_tailleur` DISABLE KEYS */;
/*!40000 ALTER TABLE `mouvements_tailleur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id_notification` int(11) NOT NULL AUTO_INCREMENT,
  `type_notification` enum('stock_faible','stock_critique','rupture_stock','vente_importante','systeme') NOT NULL,
  `titre` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `id_produit` int(11) DEFAULT NULL COMMENT 'Produit concerné si applicable',
  `niveau_urgence` enum('info','avertissement','urgent') DEFAULT 'info',
  `est_lue` tinyint(1) DEFAULT 0 COMMENT '0=Non lue, 1=Lue',
  `date_creation` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_notification`),
  KEY `idx_lue` (`est_lue`),
  KEY `idx_type` (`type_notification`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notifications système';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paiements_credit`
--

DROP TABLE IF EXISTS `paiements_credit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paiements_credit` (
  `id_paiement` int(11) NOT NULL AUTO_INCREMENT,
  `id_vente` int(11) NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `mode_paiement` enum('especes','carte','mobile_money','cheque') NOT NULL DEFAULT 'especes',
  `notes` varchar(500) NOT NULL DEFAULT '',
  `id_utilisateur` int(11) NOT NULL,
  `date_paiement` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_paiement`),
  KEY `fk_pc_utilisateur` (`id_utilisateur`),
  KEY `idx_pc_id_vente` (`id_vente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paiements_credit`
--

LOCK TABLES `paiements_credit` WRITE;
/*!40000 ALTER TABLE `paiements_credit` DISABLE KEYS */;
/*!40000 ALTER TABLE `paiements_credit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paiements_tailleur`
--

DROP TABLE IF EXISTS `paiements_tailleur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paiements_tailleur` (
  `id_paiement` int(11) NOT NULL AUTO_INCREMENT,
  `id_tailleur` int(11) NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `periode_debut` date NOT NULL,
  `periode_fin` date NOT NULL,
  `nb_commandes` int(11) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `id_depense` int(11) DEFAULT NULL COMMENT 'ID dépense caisse associée',
  `date_paiement` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_paiement`),
  KEY `id_tailleur` (`id_tailleur`),
  KEY `id_utilisateur` (`id_utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paiements_tailleur`
--

LOCK TABLES `paiements_tailleur` WRITE;
/*!40000 ALTER TABLE `paiements_tailleur` DISABLE KEYS */;
/*!40000 ALTER TABLE `paiements_tailleur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `produits`
--

DROP TABLE IF EXISTS `produits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `produits` (
  `id_produit` int(11) NOT NULL AUTO_INCREMENT,
  `code_produit` varchar(100) DEFAULT NULL COMMENT 'Code/Référence unique du produit',
  `nom_produit` varchar(255) NOT NULL COMMENT 'Nom du produit',
  `description` text DEFAULT NULL COMMENT 'Description détaillée',
  `id_categorie` int(11) DEFAULT NULL COMMENT 'Catégorie du produit',
  `id_fournisseur_principal` int(11) DEFAULT NULL COMMENT 'Fournisseur principal du produit',
  `prix_achat` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Prix d''achat (VISIBLE ADMIN SEULEMENT)',
  `prix_vente` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Prix de vente recommandé',
  `prix_vente_min` decimal(15,2) DEFAULT NULL COMMENT 'Prix de vente minimum autorisé',
  `quantite_stock` int(11) NOT NULL DEFAULT 0 COMMENT 'Quantité actuelle en stock',
  `seuil_alerte` int(11) DEFAULT 10 COMMENT 'Seuil pour alerte stock faible',
  `seuil_critique` int(11) DEFAULT 5 COMMENT 'Seuil critique (alerte rouge)',
  `unite_mesure` varchar(50) DEFAULT 'pièce' COMMENT 'Unité (pièce, kg, litre, etc.)',
  `image` varchar(255) DEFAULT NULL COMMENT 'Image du produit',
  `code_barre` varchar(100) DEFAULT NULL COMMENT 'Code-barres pour scanner',
  `emplacement` varchar(255) DEFAULT NULL COMMENT 'Emplacement dans le magasin',
  `date_entree` date DEFAULT NULL COMMENT 'Date dernière entrée en stock',
  `date_derniere_vente` datetime DEFAULT NULL COMMENT 'Date de la dernière vente',
  `nombre_ventes` int(11) DEFAULT 0 COMMENT 'Nombre total de ventes',
  `est_actif` tinyint(1) DEFAULT 1 COMMENT '0=Inactif, 1=Actif',
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `type_produit` enum('standard','service') NOT NULL DEFAULT 'standard' COMMENT 'standard=avec stock, service=sans stock',
  PRIMARY KEY (`id_produit`),
  UNIQUE KEY `code_produit` (`code_produit`),
  KEY `idx_categorie` (`id_categorie`),
  KEY `idx_stock` (`quantite_stock`),
  KEY `idx_code` (`code_produit`),
  KEY `idx_actif` (`est_actif`),
  KEY `idx_produits_stock_actif` (`quantite_stock`,`est_actif`),
  KEY `idx_fournisseur_principal` (`id_fournisseur_principal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Produits en stock avec gestion des alertes';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `produits`
--

LOCK TABLES `produits` WRITE;
/*!40000 ALTER TABLE `produits` DISABLE KEYS */;
/*!40000 ALTER TABLE `produits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_par_depot`
--

DROP TABLE IF EXISTS `stock_par_depot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_par_depot` (
  `id_stock` int(11) NOT NULL AUTO_INCREMENT,
  `id_produit` int(11) NOT NULL COMMENT 'Référence au produit',
  `id_depot` int(11) NOT NULL COMMENT 'Référence au dépôt',
  `quantite` int(11) NOT NULL DEFAULT 0 COMMENT 'Quantité dans ce dépôt',
  `seuil_alerte` int(11) DEFAULT 10 COMMENT 'Seuil d''alerte pour ce dépôt',
  `date_derniere_maj` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_stock`),
  UNIQUE KEY `unique_produit_depot` (`id_produit`,`id_depot`),
  KEY `idx_id_produit` (`id_produit`),
  KEY `idx_id_depot` (`id_depot`),
  KEY `idx_quantite` (`quantite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stock par emplacement (multi-localisation)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_par_depot`
--

LOCK TABLES `stock_par_depot` WRITE;
/*!40000 ALTER TABLE `stock_par_depot` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_par_depot` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `after_stock_par_depot_update` AFTER UPDATE ON `stock_par_depot` FOR EACH ROW BEGIN
    DECLARE delta INT;

    SET delta = NEW.quantite - OLD.quantite;

    UPDATE produits
    SET quantite_stock = quantite_stock + delta
    WHERE id_produit = NEW.id_produit;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `after_stock_par_depot_delete` AFTER DELETE ON `stock_par_depot` FOR EACH ROW BEGIN
    UPDATE produits
    SET quantite_stock = quantite_stock - OLD.quantite
    WHERE id_produit = OLD.id_produit;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `tailleurs`
--

DROP TABLE IF EXISTS `tailleurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tailleurs` (
  `id_tailleur` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) NOT NULL,
  `telephone` varchar(30) DEFAULT NULL,
  `specialite` varchar(100) DEFAULT NULL,
  `solde` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Solde acomptes accumulés',
  `notes` text DEFAULT NULL,
  `est_actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_tailleur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tailleurs`
--

LOCK TABLES `tailleurs` WRITE;
/*!40000 ALTER TABLE `tailleurs` DISABLE KEYS */;
/*!40000 ALTER TABLE `tailleurs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `utilisateurs` (
  `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT,
  `nom_complet` varchar(255) NOT NULL COMMENT 'Nom complet de l''utilisateur',
  `login` varchar(100) NOT NULL COMMENT 'Identifiant de connexion (unique)',
  `mot_de_passe` varchar(255) NOT NULL COMMENT 'Mot de passe hashé (password_hash)',
  `email` varchar(255) DEFAULT NULL COMMENT 'Adresse email',
  `telephone` varchar(50) DEFAULT NULL COMMENT 'Numéro de téléphone',
  `niveau_acces` tinyint(1) NOT NULL DEFAULT 2 COMMENT '1=Admin, 2=Vendeur',
  `id_depot` int(11) DEFAULT NULL COMMENT 'Dépôt assigné (obligatoire pour les caissiers/vendeurs)',
  `photo` varchar(255) DEFAULT NULL COMMENT 'Photo de profil',
  `est_actif` tinyint(1) DEFAULT 1 COMMENT '0=Inactif, 1=Actif',
  `date_creation` datetime DEFAULT current_timestamp() COMMENT 'Date de création du compte',
  `date_derniere_connexion` datetime DEFAULT NULL COMMENT 'Dernière connexion',
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `login` (`login`),
  KEY `idx_login` (`login`),
  KEY `idx_niveau` (`niveau_acces`),
  KEY `fk_user_depot` (`id_depot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Utilisateurs du système avec permissions';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `utilisateurs`
--

LOCK TABLES `utilisateurs` WRITE;
/*!40000 ALTER TABLE `utilisateurs` DISABLE KEYS */;
/*!40000 ALTER TABLE `utilisateurs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ventes`
--

DROP TABLE IF EXISTS `ventes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ventes` (
  `id_vente` int(11) NOT NULL AUTO_INCREMENT,
  `numero_facture` varchar(50) NOT NULL COMMENT 'Numéro unique de la facture',
  `id_client` int(11) DEFAULT NULL COMMENT 'Client (NULL = vente comptoir)',
  `id_vendeur` int(11) NOT NULL COMMENT 'Vendeur/Caissier qui a effectué la vente',
  `montant_total` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Montant total de la vente',
  `montant_ht` decimal(10,2) DEFAULT 0.00,
  `montant_remise` decimal(15,2) DEFAULT 0.00 COMMENT 'Remise accordée',
  `montant_tva` decimal(15,2) DEFAULT 0.00 COMMENT 'Montant TVA',
  `montant_paye` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Montant payé par le client',
  `montant_rendu` decimal(15,2) DEFAULT 0.00 COMMENT 'Monnaie rendue',
  `mode_paiement` enum('especes','carte','mobile_money','cheque','credit') DEFAULT 'especes',
  `date_echeance` date DEFAULT NULL,
  `statut` enum('en_cours','validee','annulee') DEFAULT 'validee',
  `notes` text DEFAULT NULL COMMENT 'Notes ou observations',
  `date_vente` datetime DEFAULT current_timestamp() COMMENT 'Date et heure de la vente',
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_confection` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Commande de confection atelier',
  `type_confection` varchar(100) DEFAULT NULL COMMENT 'Type de confection (chemise, pantalon, boubou...)',
  PRIMARY KEY (`id_vente`),
  UNIQUE KEY `numero_facture` (`numero_facture`),
  UNIQUE KEY `unique_numero_facture` (`numero_facture`),
  KEY `idx_client` (`id_client`),
  KEY `idx_vendeur` (`id_vendeur`),
  KEY `idx_date` (`date_vente`),
  KEY `idx_statut` (`statut`),
  KEY `idx_ventes_date_statut` (`date_vente`,`statut`),
  KEY `idx_ventes_mode_paiement` (`mode_paiement`),
  KEY `idx_ventes_date_echeance` (`date_echeance`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='En-têtes des ventes (factures)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventes`
--

LOCK TABLES `ventes` WRITE;
/*!40000 ALTER TABLE `ventes` DISABLE KEYS */;
/*!40000 ALTER TABLE `ventes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ventes_details`
--

DROP TABLE IF EXISTS `ventes_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ventes_details` (
  `id_detail` int(11) NOT NULL AUTO_INCREMENT,
  `id_vente` int(11) NOT NULL COMMENT 'Référence à la vente',
  `id_produit` int(11) NOT NULL COMMENT 'Produit vendu',
  `nom_produit` varchar(255) NOT NULL COMMENT 'Nom du produit (copie pour historique)',
  `quantite` int(11) NOT NULL DEFAULT 1 COMMENT 'Quantité vendue',
  `prix_unitaire` decimal(15,2) NOT NULL COMMENT 'Prix unitaire de vente',
  `prix_achat_unitaire` decimal(15,2) NOT NULL COMMENT 'Prix d''achat (pour calcul bénéfice)',
  `prix_total` decimal(15,2) NOT NULL COMMENT 'Prix total de la ligne (quantité × prix)',
  `benefice_ligne` decimal(15,2) NOT NULL COMMENT 'Bénéfice sur cette ligne',
  `remise_ligne` decimal(15,2) DEFAULT 0.00 COMMENT 'Remise sur cette ligne',
  `date_creation` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_detail`),
  KEY `idx_vente` (`id_vente`),
  KEY `idx_produit` (`id_produit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Détails des ventes (lignes de factures)';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ventes_details`
--

LOCK TABLES `ventes_details` WRITE;
/*!40000 ALTER TABLE `ventes_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `ventes_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vue_mouvements_stock_detail`
--

DROP TABLE IF EXISTS `vue_mouvements_stock_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vue_mouvements_stock_detail` (
  `code_produit` varchar(100) DEFAULT NULL,
  `cout_total` decimal(15,2) DEFAULT NULL,
  `cout_unitaire` decimal(15,2) DEFAULT NULL,
  `date_mouvement` datetime DEFAULT NULL,
  `depot_destination` varchar(255) DEFAULT NULL,
  `depot_source` varchar(255) DEFAULT NULL,
  `id_mouvement` int(11) DEFAULT NULL,
  `id_produit` int(11) DEFAULT NULL,
  `motif` varchar(255) DEFAULT NULL,
  `nom_fournisseur` varchar(255) DEFAULT NULL,
  `nom_produit` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `numero_facture` varchar(50) DEFAULT NULL,
  `quantite` int(11) DEFAULT NULL,
  `quantite_apres` int(11) DEFAULT NULL,
  `quantite_avant` int(11) DEFAULT NULL,
  `type_mouvement` enum('entree','sortie','ajustement','retour','transfert','inventaire','perte') DEFAULT NULL,
  `type_mouvement_libelle` varchar(14) DEFAULT NULL,
  `unite_mesure` varchar(50) DEFAULT NULL,
  `utilisateur` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vue_mouvements_stock_detail`
--

LOCK TABLES `vue_mouvements_stock_detail` WRITE;
/*!40000 ALTER TABLE `vue_mouvements_stock_detail` DISABLE KEYS */;
/*!40000 ALTER TABLE `vue_mouvements_stock_detail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vue_produits_alertes`
--

DROP TABLE IF EXISTS `vue_produits_alertes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vue_produits_alertes` (
  `code_produit` varchar(100) DEFAULT NULL,
  `date_entree` date DEFAULT NULL,
  `id_produit` int(11) DEFAULT NULL,
  `niveau_alerte` varchar(8) DEFAULT NULL,
  `nom_categorie` varchar(255) DEFAULT NULL,
  `nom_produit` varchar(255) DEFAULT NULL,
  `quantite_stock` int(11) DEFAULT NULL,
  `seuil_alerte` int(11) DEFAULT NULL,
  `seuil_critique` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vue_produits_alertes`
--

LOCK TABLES `vue_produits_alertes` WRITE;
/*!40000 ALTER TABLE `vue_produits_alertes` DISABLE KEYS */;
/*!40000 ALTER TABLE `vue_produits_alertes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vue_statistiques_ventes`
--

DROP TABLE IF EXISTS `vue_statistiques_ventes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vue_statistiques_ventes` (
  `benefice_total` decimal(37,2) DEFAULT NULL,
  `chiffre_affaires` decimal(37,2) DEFAULT NULL,
  `date_vente` date DEFAULT NULL,
  `nombre_ventes` bigint(20) DEFAULT NULL,
  `vendeur` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vue_statistiques_ventes`
--

LOCK TABLES `vue_statistiques_ventes` WRITE;
/*!40000 ALTER TABLE `vue_statistiques_ventes` DISABLE KEYS */;
/*!40000 ALTER TABLE `vue_statistiques_ventes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vue_stock_global`
--

DROP TABLE IF EXISTS `vue_stock_global`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vue_stock_global` (
  `code_produit` varchar(100) DEFAULT NULL,
  `est_actif` tinyint(1) DEFAULT NULL,
  `id_categorie` int(11) DEFAULT NULL,
  `id_produit` int(11) DEFAULT NULL,
  `nom_categorie` varchar(255) DEFAULT NULL,
  `nom_produit` varchar(255) DEFAULT NULL,
  `prix_achat` decimal(15,2) DEFAULT NULL,
  `prix_vente` decimal(15,2) DEFAULT NULL,
  `seuil_alerte` int(11) DEFAULT NULL,
  `seuil_critique` int(11) DEFAULT NULL,
  `statut_stock` varchar(8) DEFAULT NULL,
  `stock_total` decimal(32,0) DEFAULT NULL,
  `unite_mesure` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vue_stock_global`
--

LOCK TABLES `vue_stock_global` WRITE;
/*!40000 ALTER TABLE `vue_stock_global` DISABLE KEYS */;
/*!40000 ALTER TABLE `vue_stock_global` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-19 14:41:22
