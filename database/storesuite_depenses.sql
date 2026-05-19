-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : sam. 28 mars 2026 à 12:49
-- Version du serveur : 9.1.0
-- Version de PHP : 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `storesuite_depenses`
--

-- --------------------------------------------------------

--
-- Structure de la table `caisse`
--

DROP TABLE IF EXISTS `caisse`;
CREATE TABLE IF NOT EXISTS `caisse` (
  `id_caisse` int NOT NULL AUTO_INCREMENT,
  `date_ouverture` datetime NOT NULL,
  `date_fermeture` datetime DEFAULT NULL,
  `solde_ouverture` decimal(15,2) NOT NULL DEFAULT '0.00',
  `solde_fermeture` decimal(15,2) DEFAULT NULL,
  `solde_theorique` decimal(15,2) DEFAULT NULL COMMENT 'Calculé automatiquement à la fermeture',
  `ecart` decimal(15,2) DEFAULT NULL COMMENT 'solde_fermeture - solde_theorique',
  `id_utilisateur` int NOT NULL,
  `statut` enum('ouverte','fermee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ouverte',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_caisse`),
  KEY `fk_caisse_user` (`id_utilisateur`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `caisse`
--

INSERT INTO `caisse` (`id_caisse`, `date_ouverture`, `date_fermeture`, `solde_ouverture`, `solde_fermeture`, `solde_theorique`, `ecart`, `id_utilisateur`, `statut`, `notes`, `date_creation`, `date_modification`) VALUES
(1, '2026-03-26 08:58:21', '2026-03-27 09:32:01', 0.00, 150000.00, 150000.00, 0.00, 1, 'fermee', 'Caisse', '2026-03-26 06:58:21', '2026-03-27 09:32:01');

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id_categorie` int NOT NULL AUTO_INCREMENT,
  `nom_categorie` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom de la catégorie',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Description de la catégorie',
  `icone` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Icône ou classe CSS',
  `couleur` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Couleur associée (format HEX)',
  `ordre_affichage` int DEFAULT '0' COMMENT 'Ordre d''affichage',
  `est_actif` tinyint(1) DEFAULT '1' COMMENT '0=Inactif, 1=Actif',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_categorie`),
  KEY `idx_actif` (`est_actif`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catégories de produits';

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id_categorie`, `nom_categorie`, `description`, `icone`, `couleur`, `ordre_affichage`, `est_actif`, `date_creation`, `date_modification`) VALUES
(1, 'Électronique', 'Téléphones, ordinateurs, accessoires', 'ti-device-laptop', '#3498db', 1, 1, '2026-01-08 13:39:48', '2026-01-08 13:39:48'),
(2, 'Électroménager', 'Réfrigérateurs, télévisions, cuisinières', 'ti-device-tv', '#e74c3c', 2, 1, '2026-01-08 13:39:48', '2026-01-08 13:39:48'),
(3, 'Meubles', 'Tables, chaises, armoires', 'ti-armchair', '#9b59b6', 3, 1, '2026-01-08 13:39:48', '2026-01-08 13:39:48'),
(4, 'Vêtements', 'Habits, chaussures, accessoires', 'ti-hanger', '#1abc9c', 4, 1, '2026-01-08 13:39:48', '2026-01-08 13:39:48'),
(5, 'Alimentation', 'Produits alimentaires', 'ti-shopping-cart', '#f39c12', 5, 1, '2026-01-08 13:39:48', '2026-01-08 13:39:48'),
(6, 'Cat X', '', NULL, NULL, 0, 1, '2026-03-11 10:06:10', '2026-03-11 09:06:10'),
(7, 'XC', '', NULL, NULL, 0, 1, '2026-03-13 18:00:23', '2026-03-13 17:00:23');

-- --------------------------------------------------------

--
-- Structure de la table `categories_depenses`
--

DROP TABLE IF EXISTS `categories_depenses`;
CREATE TABLE IF NOT EXISTS `categories_depenses` (
  `id_categorie` int NOT NULL AUTO_INCREMENT,
  `nom_categorie` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `couleur` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#6c757d',
  `icone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'receipt',
  `est_actif` tinyint(1) NOT NULL DEFAULT '1',
  `ordre_affichage` int NOT NULL DEFAULT '0',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_categorie`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categories_depenses`
--

INSERT INTO `categories_depenses` (`id_categorie`, `nom_categorie`, `description`, `couleur`, `icone`, `est_actif`, `ordre_affichage`, `date_creation`) VALUES
(1, 'Loyer & Charges', 'Loyer, eau, électricité, internet', '#e74c3c', 'home', 1, 1, '2026-03-26 06:54:17'),
(2, 'Salaires', 'Salaires et avances sur salaire', '#9b59b6', 'users', 1, 2, '2026-03-26 06:54:17'),
(3, 'Approvisionnement', 'Achat de marchandises et matières premières', '#3498db', 'package', 1, 3, '2026-03-26 06:54:17'),
(4, 'Transport', 'Carburant, livraison, frais de déplacement', '#f39c12', 'truck', 1, 4, '2026-03-26 06:54:17'),
(5, 'Fournitures', 'Matériel de bureau et consommables', '#1abc9c', 'tool', 1, 5, '2026-03-26 06:54:17'),
(6, 'Entretien', 'Réparations et maintenance du local/équipements', '#e67e22', 'settings', 1, 6, '2026-03-26 06:54:17'),
(7, 'Taxes & Impôts', 'Taxes, droits et charges fiscales', '#c0392b', 'file-text', 1, 7, '2026-03-26 06:54:17'),
(8, 'Divers', 'Autres dépenses non catégorisées', '#95a5a6', 'more-horizontal', 1, 8, '2026-03-26 06:54:17');

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

DROP TABLE IF EXISTS `clients`;
CREATE TABLE IF NOT EXISTS `clients` (
  `id_client` int NOT NULL AUTO_INCREMENT,
  `nom_client` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom du client',
  `telephone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Téléphone',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Email',
  `adresse` text COLLATE utf8mb4_unicode_ci COMMENT 'Adresse complète',
  `type_client` enum('particulier','entreprise') COLLATE utf8mb4_unicode_ci DEFAULT 'particulier',
  `numero_fiscal` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Numéro fiscal (pour entreprises)',
  `total_achats` decimal(15,2) DEFAULT '0.00' COMMENT 'Total des achats',
  `nombre_achats` int DEFAULT '0' COMMENT 'Nombre d''achats',
  `date_dernier_achat` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Notes sur le client',
  `est_actif` tinyint(1) DEFAULT '1',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_client`),
  KEY `idx_telephone` (`telephone`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Base de données clients';

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id_client`, `nom_client`, `telephone`, `email`, `adresse`, `type_client`, `numero_fiscal`, `total_achats`, `nombre_achats`, `date_dernier_achat`, `notes`, `est_actif`, `date_creation`, `date_modification`) VALUES
(2, 'Boly', '+22678451245', '', '', 'particulier', NULL, 0.00, 0, NULL, NULL, 1, '2026-03-11 10:22:19', '2026-03-11 09:22:19');

-- --------------------------------------------------------

--
-- Structure de la table `configuration`
--

DROP TABLE IF EXISTS `configuration`;
CREATE TABLE IF NOT EXISTS `configuration` (
  `id_config` int NOT NULL AUTO_INCREMENT,
  `nom_boutique` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom de la boutique/entreprise',
  `slogan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Slogan ou description courte',
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Chemin vers le fichier logo',
  `couleur_primaire` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#e6e64c' COMMENT 'Couleur principale (format HEX)',
  `couleur_secondaire` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#556a94' COMMENT 'Couleur secondaire (format HEX)',
  `adresse` text COLLATE utf8mb4_unicode_ci COMMENT 'Adresse complète de l''entreprise',
  `telephone` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Numéro(s) de téléphone',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Adresse email',
  `site_web` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Site web de l''entreprise',
  `num_registre_commerce` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Numéro d''enregistrement (RCCM, etc.)',
  `num_impot` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Numéro fiscal/TVA',
  `devise` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '$' COMMENT 'Symbole de la devise utilisée',
  `taux_tva` decimal(5,2) DEFAULT '0.00' COMMENT 'Taux de TVA par défaut (%)',
  `fuseau_horaire` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Africa/Lubumbashi' COMMENT 'Fuseau horaire',
  `langue` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'fr' COMMENT 'Langue du système (fr, en, etc.)',
  `est_configure` tinyint(1) DEFAULT '0' COMMENT '0=Non configuré, 1=Configuré',
  `date_configuration` datetime DEFAULT NULL COMMENT 'Date de première configuration',
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_config`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Paramètres globaux du système';

--
-- Déchargement des données de la table `configuration`
--

INSERT INTO `configuration` (`id_config`, `nom_boutique`, `slogan`, `logo`, `couleur_primaire`, `couleur_secondaire`, `adresse`, `telephone`, `email`, `site_web`, `num_registre_commerce`, `num_impot`, `devise`, `taux_tva`, `fuseau_horaire`, `langue`, `est_configure`, `date_configuration`, `date_modification`) VALUES
(1, 'Shop', '', '', '#206bc4', '#1a5aa8', '', '', '', '', '', '', 'XOF', 18.00, 'Africa/Ouagadougou', 'fr', 1, '2026-03-11 00:39:54', '2026-03-11 12:35:18');

-- --------------------------------------------------------

--
-- Structure de la table `depenses`
--

DROP TABLE IF EXISTS `depenses`;
CREATE TABLE IF NOT EXISTS `depenses` (
  `id_depense` int NOT NULL AUTO_INCREMENT,
  `id_categorie` int NOT NULL,
  `libelle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `id_caisse` int DEFAULT NULL COMMENT 'Caisse sur laquelle la dépense est imputée',
  `id_utilisateur` int NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `date_depense` datetime NOT NULL,
  `statut` enum('validee','annulee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'validee',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_depense`),
  KEY `fk_dep_user` (`id_utilisateur`),
  KEY `idx_dep_caisse` (`id_caisse`),
  KEY `idx_dep_categorie` (`id_categorie`),
  KEY `idx_dep_date` (`date_depense`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `depots`
--

DROP TABLE IF EXISTS `depots`;
CREATE TABLE IF NOT EXISTS `depots` (
  `id_depot` int NOT NULL AUTO_INCREMENT,
  `nom_depot` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom du dépôt/emplacement',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Description de l''emplacement',
  `type_depot` enum('magasin','depot','entrepot','autre') COLLATE utf8mb4_unicode_ci DEFAULT 'depot' COMMENT 'Type d''emplacement',
  `adresse` text COLLATE utf8mb4_unicode_ci COMMENT 'Adresse physique',
  `responsable` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Responsable du dépôt',
  `telephone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Téléphone du dépôt',
  `capacite` int DEFAULT NULL COMMENT 'Capacité maximale (unités)',
  `est_principal` tinyint(1) DEFAULT '0' COMMENT '1=Dépôt principal (Magasin)',
  `est_actif` tinyint(1) DEFAULT '1' COMMENT '0=Inactif, 1=Actif',
  `ordre_affichage` int DEFAULT '0',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_depot`),
  UNIQUE KEY `unique_nom_depot` (`nom_depot`),
  KEY `idx_est_actif` (`est_actif`),
  KEY `idx_est_principal` (`est_principal`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Emplacements de stockage';

--
-- Déchargement des données de la table `depots`
--

INSERT INTO `depots` (`id_depot`, `nom_depot`, `description`, `type_depot`, `adresse`, `responsable`, `telephone`, `capacite`, `est_principal`, `est_actif`, `ordre_affichage`, `date_creation`, `date_modification`) VALUES
(1, 'Magasin', 'Emplacement principal de vente (par défaut)', 'magasin', NULL, NULL, NULL, NULL, 1, 1, 1, '2026-03-10 22:35:51', '2026-03-10 22:35:51');

-- --------------------------------------------------------

--
-- Structure de la table `details_vente`
--

DROP TABLE IF EXISTS `details_vente`;
CREATE TABLE IF NOT EXISTS `details_vente` (
  `id_detail` int NOT NULL AUTO_INCREMENT,
  `id_vente` int NOT NULL COMMENT 'Référence à la vente',
  `id_produit` int NOT NULL COMMENT 'Produit vendu',
  `nom_produit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom du produit',
  `quantite` int NOT NULL DEFAULT '1' COMMENT 'Quantité vendue',
  `prix_unitaire` decimal(15,2) NOT NULL COMMENT 'Prix unitaire',
  `prix_achat_unitaire` decimal(15,2) NOT NULL COMMENT 'Prix achat',
  `prix_total` decimal(15,2) NOT NULL COMMENT 'Total ligne',
  `benefice_ligne` decimal(15,2) NOT NULL COMMENT 'Bénéfice ligne',
  `remise_ligne` decimal(15,2) DEFAULT '0.00' COMMENT 'Remise ligne',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_detail`),
  KEY `idx_vente` (`id_vente`),
  KEY `idx_produit` (`id_produit`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `details_vente`
--

INSERT INTO `details_vente` (`id_detail`, `id_vente`, `id_produit`, `nom_produit`, `quantite`, `prix_unitaire`, `prix_achat_unitaire`, `prix_total`, `benefice_ligne`, `remise_ligne`, `date_creation`) VALUES
(13, 1, 1, '', 1, 2000.00, 0.00, 0.00, 0.00, 0.00, '2026-03-11 00:29:32'),
(14, 2, 3, '', 1, 125000.00, 0.00, 0.00, 0.00, 0.00, '2026-03-11 10:11:43'),
(15, 2, 1, '', 1, 2000.00, 0.00, 0.00, 0.00, 0.00, '2026-03-11 10:11:43'),
(16, 3, 3, '', 1, 125000.00, 0.00, 0.00, 0.00, 0.00, '2026-03-11 10:22:48'),
(17, 4, 2, '', 2, 2500.00, 0.00, 0.00, 0.00, 0.00, '2026-03-11 10:31:49'),
(18, 5, 5, '', 5, 8000.00, 0.00, 0.00, 0.00, 0.00, '2026-03-11 11:59:24'),
(19, 6, 3, '', 1, 125000.00, 0.00, 0.00, 0.00, 0.00, '2026-03-12 15:51:16'),
(20, 6, 1, '', 1, 2000.00, 0.00, 0.00, 0.00, 0.00, '2026-03-12 15:51:16'),
(21, 7, 3, '', 1, 125000.00, 0.00, 0.00, 0.00, 0.00, '2026-03-12 15:51:22'),
(22, 7, 1, '', 1, 2000.00, 0.00, 0.00, 0.00, 0.00, '2026-03-12 15:51:22'),
(23, 8, 8, '', 1, 150000.00, 0.00, 0.00, 0.00, 0.00, '2026-03-13 18:05:20'),
(24, 8, 7, '', 1, 1000.00, 0.00, 0.00, 0.00, 0.00, '2026-03-13 18:05:20'),
(25, 9, 7, '', 3, 1000.00, 0.00, 0.00, 0.00, 0.00, '2026-03-24 13:04:39'),
(26, 10, 7, '', 1, 1000.00, 0.00, 0.00, 0.00, 0.00, '2026-03-25 12:08:02'),
(27, 10, 3, '', 1, 125000.00, 0.00, 0.00, 0.00, 0.00, '2026-03-25 12:08:03'),
(28, 11, 9, '', 1, 10000.00, 0.00, 0.00, 0.00, 0.00, '2026-03-25 13:20:51'),
(29, 12, 7, '', 5, 1000.00, 0.00, 0.00, 0.00, 0.00, '2026-03-25 13:25:01'),
(30, 13, 8, '', 1, 150000.00, 0.00, 0.00, 0.00, 0.00, '2026-03-26 13:50:43');

-- --------------------------------------------------------

--
-- Structure de la table `fournisseurs`
--

DROP TABLE IF EXISTS `fournisseurs`;
CREATE TABLE IF NOT EXISTS `fournisseurs` (
  `id_fournisseur` int NOT NULL AUTO_INCREMENT,
  `nom_fournisseur` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom du fournisseur',
  `contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Personne de contact',
  `telephone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Numéro de téléphone',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Adresse email',
  `adresse` text COLLATE utf8mb4_unicode_ci COMMENT 'Adresse complète',
  `pays` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Pays',
  `ville` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ville',
  `conditions_paiement` text COLLATE utf8mb4_unicode_ci COMMENT 'Conditions de paiement',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Notes diverses',
  `est_actif` tinyint(1) DEFAULT '1' COMMENT '0=Inactif, 1=Actif',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_fournisseur`),
  KEY `idx_nom_fournisseur` (`nom_fournisseur`),
  KEY `idx_est_actif` (`est_actif`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fournisseurs de produits';

--
-- Déchargement des données de la table `fournisseurs`
--

INSERT INTO `fournisseurs` (`id_fournisseur`, `nom_fournisseur`, `contact`, `telephone`, `email`, `adresse`, `pays`, `ville`, `conditions_paiement`, `notes`, `est_actif`, `date_creation`, `date_modification`) VALUES
(1, 'Fournisseur général', 'Divers', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2026-01-15 09:11:04', '2026-01-15 07:11:04'),
(2, 'Fournisseur Test', '', '', '', '', NULL, NULL, NULL, NULL, 1, '2026-03-11 00:08:51', '2026-03-10 23:08:51'),
(3, 'Fournisseur X', 'Cisse', '', '', '', NULL, NULL, NULL, NULL, 1, '2026-03-11 10:06:32', '2026-03-11 09:06:32');

-- --------------------------------------------------------

--
-- Structure de la table `logs_activites`
--

DROP TABLE IF EXISTS `logs_activites`;
CREATE TABLE IF NOT EXISTS `logs_activites` (
  `id_log` int NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int DEFAULT NULL,
  `type_action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type d''action (connexion, vente, modification, etc.)',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Description détaillée de l''action',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Adresse IP',
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Navigateur/Device',
  `donnees_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Données supplémentaires en JSON',
  `date_action` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_utilisateur` (`id_utilisateur`),
  KEY `idx_type` (`type_action`),
  KEY `idx_date` (`date_action`)
) ENGINE=MyISAM AUTO_INCREMENT=111 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `logs_activites`
--

INSERT INTO `logs_activites` (`id_log`, `id_utilisateur`, `type_action`, `description`, `ip_address`, `user_agent`, `donnees_json`, `date_action`) VALUES
(1, 2, 'configuration_initiale', 'Configuration initiale du système effectuée', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"nom_boutique\":\"Ma Super Boutique Test\",\"admin_login\":\"admin\"}', '2026-01-09 09:33:05'),
(2, 3, 'configuration_initiale', 'Configuration initiale du système effectuée', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"nom_boutique\":\"CALEB SHOP\",\"admin_login\":\"admin\"}', '2026-01-09 09:42:27'),
(3, 3, 'connexion', 'Connexion réussie de Emmanuel K', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-09 08:43:43'),
(4, 3, 'deconnexion', 'Déconnexion de Emmanuel K', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-09 09:07:31'),
(5, 3, 'connexion', 'Connexion réussie de Emmanuel K', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-09 09:07:50'),
(6, 3, 'connexion', 'Connexion réussie de Emmanuel K', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-09 17:23:31'),
(7, 3, 'VENTE', 'Nouvelle vente créée: FAC-20260109-0001 (1392 $)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"id_vente\":\"4\",\"numero_facture\":\"FAC-20260109-0001\",\"montant\":1392}', '2026-01-09 19:49:49'),
(8, 3, 'VENTE', 'Nouvelle vente créée: FAC-20260109-0002 (1392 $)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"id_vente\":\"5\",\"numero_facture\":\"FAC-20260109-0002\",\"montant\":1392}', '2026-01-09 19:50:24'),
(9, 3, 'VENTE_ANNULEE', 'Vente annulée: FAC-20260109-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"id_vente\":5,\"numero_facture\":\"FAC-20260109-0002\",\"montant\":\"1392.00\"}', '2026-01-09 20:07:58'),
(10, 3, 'VENTE_SUPPRIMEE', 'Vente supprimée définitivement: FAC-20260109-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"id_vente\":5,\"numero_facture\":\"FAC-20260109-0002\",\"montant\":\"1392.00\"}', '2026-01-09 20:19:35'),
(11, 3, 'VENTE_ANNULEE', 'Vente annulée: FAC-20260109-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"id_vente\":4,\"numero_facture\":\"FAC-20260109-0001\",\"montant\":\"1392.00\"}', '2026-01-09 20:22:15'),
(12, 3, 'VENTE_SUPPRIMEE', 'Vente supprimée définitivement: FAC-20260109-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"id_vente\":4,\"numero_facture\":\"FAC-20260109-0001\",\"montant\":\"1392.00\"}', '2026-01-09 20:22:21'),
(13, 3, 'VENTE', 'Nouvelle vente créée: FAC-20260109-0001 (1200 $)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"id_vente\":\"6\",\"numero_facture\":\"FAC-20260109-0001\",\"montant\":1200}', '2026-01-09 20:24:50'),
(14, 3, 'VENTE_ANNULEE', 'Vente annulée: FAC-20260109-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"id_vente\":6,\"numero_facture\":\"FAC-20260109-0001\",\"montant\":\"1200.00\"}', '2026-01-09 20:28:54'),
(15, 3, 'VENTE_SUPPRIMEE', 'Vente supprimée définitivement: FAC-20260109-0001', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"id_vente\":6,\"numero_facture\":\"FAC-20260109-0001\",\"montant\":\"1200.00\"}', '2026-01-09 20:29:05'),
(16, 3, 'VENTE', 'Nouvelle vente créée: FAC-20260109-0001 (1200 $)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"id_vente\":\"7\",\"numero_facture\":\"FAC-20260109-0001\",\"montant\":1200}', '2026-01-09 20:29:28'),
(17, 3, 'VENTE', 'Nouvelle vente créée: FAC-20260109-0002 (1200 $)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"id_vente\":\"8\",\"numero_facture\":\"FAC-20260109-0002\",\"montant\":1200}', '2026-01-09 20:53:25'),
(18, 3, 'connexion', 'Connexion réussie de Emmanuel K', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-10 06:55:55'),
(19, 3, 'vente_restauree', 'Restauration de la vente FAC-20260109-0002', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"id_vente\":8,\"numero_facture\":\"FAC-20260109-0002\"}', '2026-01-10 07:44:56'),
(20, 3, 'VENTE', 'Nouvelle vente créée: FAC-20260110-0003 (1200 $)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"id_vente\":\"9\",\"numero_facture\":\"FAC-20260110-0003\",\"montant\":1200}', '2026-01-10 07:53:49'),
(21, 3, 'VENTE', 'Nouvelle vente créée: FAC-20260110-0004 (1200 $)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"id_vente\":\"10\",\"numero_facture\":\"FAC-20260110-0004\",\"montant\":1200}', '2026-01-10 07:54:55'),
(22, 3, 'connexion', 'Connexion réussie de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-10 08:18:09'),
(23, 3, 'connexion', 'Connexion réussie de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', NULL, '2026-01-10 08:25:52'),
(24, 3, 'REINIT', 'Suppression de toutes les ventes', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"type\":\"ventes\"}', '2026-01-10 09:41:08'),
(25, 3, 'REINIT', 'Suppression de tous les clients', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"type\":\"clients\"}', '2026-01-10 09:45:06'),
(26, 3, 'deconnexion', 'Déconnexion de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-10 10:12:07'),
(27, 3, 'connexion', 'Connexion réussie de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-10 10:31:30'),
(28, 3, 'VENTE', 'Nouvelle vente créée: FAC-20260110-0001 (1200 $)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"id_vente\":\"11\",\"numero_facture\":\"FAC-20260110-0001\",\"montant\":1200}', '2026-01-10 12:18:06'),
(29, 3, 'connexion', 'Connexion réussie de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-10 19:42:16'),
(30, 3, 'deconnexion', 'Déconnexion de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-10 21:21:20'),
(31, 4, 'connexion', 'Connexion réussie de FEFE3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-10 21:21:36'),
(32, 4, 'deconnexion', 'Déconnexion de FEFE3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-10 23:46:42'),
(33, 3, 'connexion', 'Connexion réussie de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-10 23:46:50'),
(34, 3, 'connexion', 'Connexion réussie de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-11 19:11:44'),
(35, 3, 'deconnexion', 'Déconnexion de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-11 19:37:25'),
(36, 4, 'connexion', 'Connexion réussie de FEFE3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-11 19:37:29'),
(37, 4, 'VENTE', 'Nouvelle vente créée: FAC-20260111-0002 (1200 CDF)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"id_vente\":\"12\",\"numero_facture\":\"FAC-20260111-0002\",\"montant\":1200}', '2026-01-11 19:58:32'),
(38, NULL, 'connexion_echouee', 'Tentative de connexion échouée pour l\'utilisateur : admin', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 12:59:20'),
(39, NULL, 'connexion_echouee', 'Tentative de connexion échouée pour l\'utilisateur : admin', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 12:59:29'),
(40, NULL, 'connexion_echouee', 'Tentative de connexion échouée pour l\'utilisateur : admin', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 12:59:45'),
(41, NULL, 'connexion_echouee', 'Tentative de connexion échouée pour l\'utilisateur : admin', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 12:59:53'),
(42, NULL, 'connexion_echouee', 'Tentative de connexion échouée pour l\'utilisateur : admin', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:01:04'),
(43, NULL, 'connexion_echouee', 'Tentative de connexion échouée pour l\'utilisateur : admin', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:01:12'),
(44, NULL, 'connexion_echouee', 'Tentative de connexion échouée pour l\'utilisateur : admin', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:01:26'),
(45, NULL, 'connexion_echouee', 'Tentative de connexion échouée pour l\'utilisateur : admin', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:02:48'),
(46, NULL, 'connexion_echouee', 'Tentative de connexion échouée pour l\'utilisateur : admin', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:02:53'),
(47, NULL, 'connexion_echouee', 'Tentative de connexion échouée pour l\'utilisateur : admin', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:03:11'),
(48, NULL, 'connexion_echouee', 'Tentative de connexion échouée pour l\'utilisateur : fefe', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:03:34'),
(49, NULL, 'connexion_echouee', 'Tentative de connexion échouée pour l\'utilisateur : admin@exemple.com', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:06:35'),
(50, NULL, 'connexion_echouee', 'Tentative de connexion échouée pour l\'utilisateur : admin@exemple.com', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:06:45'),
(51, 3, 'connexion', 'Connexion réussie de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:07:39'),
(52, 3, 'deconnexion', 'Déconnexion de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:08:56'),
(53, 3, 'connexion', 'Connexion réussie de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:10:52'),
(54, 3, 'deconnexion', 'Déconnexion de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 13:11:08'),
(55, 3, 'connexion', 'Connexion réussie de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-12 15:33:41'),
(56, 3, 'connexion', 'Connexion réussie de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-13 12:04:34'),
(57, 3, 'deconnexion', 'Déconnexion de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-13 12:04:40'),
(58, 3, 'connexion', 'Connexion réussie de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-13 12:10:26'),
(59, 3, 'deconnexion', 'Déconnexion de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-13 12:10:31'),
(60, 3, 'connexion', 'Connexion réussie de EMMANUEL BARAKA', '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', NULL, '2026-01-15 08:46:27'),
(61, 1, 'configuration_initiale', 'Configuration initiale du système effectuée', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '{\"nom_boutique\":\"Shop\",\"admin_login\":\"elbrahms\"}', '2026-03-11 00:39:54'),
(62, 1, 'connexion', 'Connexion réussie de Elbrahms', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', NULL, '2026-03-10 23:40:12'),
(63, 1, 'VENTE', 'Nouvelle vente créée: FAC-20260311-0001 (2000 XOF)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '{\"id_vente\":\"1\",\"numero_facture\":\"FAC-20260311-0001\",\"montant\":2000}', '2026-03-11 00:29:32'),
(64, 1, 'connexion', 'Connexion réussie de Elbrahms', '129.222.104.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', NULL, '2026-03-11 00:59:33'),
(65, 1, 'connexion', 'Connexion réussie de Elbrahms', '129.222.104.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', NULL, '2026-03-11 01:38:30'),
(66, 1, 'connexion', 'Connexion réussie de Elbrahms', '102.180.14.67', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/29.0 Chrome/136.0.0.0 Mobile Safari/537.36', NULL, '2026-03-11 08:21:48'),
(67, 1, 'connexion', 'Connexion réussie de Elbrahms', '129.222.104.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', NULL, '2026-03-11 09:36:19'),
(68, NULL, 'connexion_echouee', 'Tentative de connexion échouée pour l\'utilisateur : elbrahms', '102.180.20.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', NULL, '2026-03-11 09:38:14'),
(69, 1, 'connexion', 'Connexion réussie de Elbrahms', '102.180.20.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', NULL, '2026-03-11 09:38:28'),
(70, 1, 'VENTE', 'Nouvelle vente créée: FAC-20260311-0002 (127000 XOF)', '102.180.20.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '{\"id_vente\":\"2\",\"numero_facture\":\"FAC-20260311-0002\",\"montant\":127000}', '2026-03-11 10:11:43'),
(71, 1, 'VENTE', 'Nouvelle vente créée: FAC-20260311-0003 (125000 XOF)', '102.180.20.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '{\"id_vente\":\"3\",\"numero_facture\":\"FAC-20260311-0003\",\"montant\":125000}', '2026-03-11 10:22:48'),
(72, 1, 'VENTE', 'Nouvelle vente créée: FAC-20260311-0004 (5000 XOF)', '102.180.20.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '{\"id_vente\":\"4\",\"numero_facture\":\"FAC-20260311-0004\",\"montant\":5000}', '2026-03-11 10:31:49'),
(73, 1, 'connexion', 'Connexion réussie de Elbrahms', '129.222.104.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', NULL, '2026-03-11 10:32:16'),
(74, 1, 'connexion', 'Connexion réussie de Elbrahms', '129.222.104.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', NULL, '2026-03-11 11:36:35'),
(75, NULL, 'connexion_echouee', 'Tentative de connexion échouée pour l\'utilisateur : elbrahms', '102.180.20.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', NULL, '2026-03-11 11:57:51'),
(76, 1, 'connexion', 'Connexion réussie de Elbrahms', '102.180.20.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', NULL, '2026-03-11 11:58:03'),
(77, 1, 'VENTE', 'Nouvelle vente créée: FAC-20260311-0005 (40000 XOF)', '102.180.20.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '{\"id_vente\":\"5\",\"numero_facture\":\"FAC-20260311-0005\",\"montant\":40000}', '2026-03-11 11:59:24'),
(78, 1, 'connexion', 'Connexion réussie de Elbrahms', '102.180.20.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', NULL, '2026-03-11 13:03:10'),
(79, 1, 'CREDIT_PAYMENT', 'Paiement crédit FAC-20260311-0005 : 10000 XOF', '102.180.20.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '{\"id_vente\":5,\"montant\":10000}', '2026-03-11 13:03:44'),
(80, 1, 'connexion', 'Connexion réussie de Elbrahms', '102.180.14.67', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', NULL, '2026-03-11 17:50:22'),
(81, NULL, 'connexion_echouee', 'Tentative de connexion échouée pour l\'utilisateur : elbrahms', '102.180.5.152', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', NULL, '2026-03-12 15:38:13'),
(82, NULL, 'connexion_echouee', 'Tentative de connexion échouée pour l\'utilisateur : elbrahms', '102.180.5.152', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', NULL, '2026-03-12 15:39:12'),
(83, 1, 'connexion', 'Connexion réussie de Elbrahms', '102.180.5.152', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', NULL, '2026-03-12 15:39:21'),
(84, 1, 'VENTE', 'Nouvelle vente créée: FAC-20260312-0006 (127000 XOF)', '102.180.5.152', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '{\"id_vente\":\"6\",\"numero_facture\":\"FAC-20260312-0006\",\"montant\":127000}', '2026-03-12 15:51:16'),
(85, 1, 'VENTE', 'Nouvelle vente créée: FAC-20260312-0007 (127000 XOF)', '102.180.5.152', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:147.0) Gecko/20100101 Firefox/147.0', '{\"id_vente\":\"7\",\"numero_facture\":\"FAC-20260312-0007\",\"montant\":127000}', '2026-03-12 15:51:22'),
(86, NULL, 'connexion_echouee', 'Tentative de connexion échouée pour l\'utilisateur : elbrahms', '102.180.5.152', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/29.0 Chrome/136.0.0.0 Mobile Safari/537.36', NULL, '2026-03-12 16:15:47'),
(87, 1, 'connexion', 'Connexion réussie de Elbrahms', '102.180.5.152', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/29.0 Chrome/136.0.0.0 Mobile Safari/537.36', NULL, '2026-03-12 16:15:58'),
(88, 1, 'connexion', 'Connexion réussie de Elbrahms', '102.180.145.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', NULL, '2026-03-13 17:56:57'),
(89, 1, 'VENTE', 'Nouvelle vente créée: FAC-20260313-0008 (151000 XOF)', '102.180.145.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '{\"id_vente\":\"8\",\"numero_facture\":\"FAC-20260313-0008\",\"montant\":151000}', '2026-03-13 18:05:20'),
(90, 1, 'connexion', 'Connexion réussie de Elbrahms', '102.180.145.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', NULL, '2026-03-13 18:12:27'),
(91, 1, 'connexion', 'Connexion réussie de Elbrahms', '102.180.145.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', NULL, '2026-03-13 18:12:38'),
(92, 1, 'connexion', 'Connexion réussie de Elbrahms', '102.180.145.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', NULL, '2026-03-14 11:18:08'),
(93, 1, 'connexion', 'Connexion réussie de Elbrahms', '102.180.20.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', NULL, '2026-03-23 16:18:13'),
(94, 1, 'connexion', 'Connexion réussie de Elbrahms', '102.180.20.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', NULL, '2026-03-24 11:39:43'),
(95, 1, 'connexion', 'Connexion réussie de Elbrahms', '102.180.20.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', NULL, '2026-03-24 12:48:59'),
(96, 1, 'VENTE', 'Nouvelle vente créée: FAC-20260324-0009 (3000 XOF)', '102.180.20.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '{\"id_vente\":\"9\",\"numero_facture\":\"FAC-20260324-0009\",\"montant\":3000}', '2026-03-24 13:04:39'),
(97, 1, 'connexion', 'Connexion réussie de Elbrahms', '197.239.114.61', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/29.0 Chrome/136.0.0.0 Mobile Safari/537.36', NULL, '2026-03-25 11:59:32'),
(98, 1, 'connexion', 'Connexion réussie de Elbrahms', '41.138.99.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', NULL, '2026-03-25 12:05:11'),
(99, 1, 'VENTE', 'Nouvelle vente créée: FAC-20260325-0010 (126000 XOF)', '41.138.99.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '{\"id_vente\":\"10\",\"numero_facture\":\"FAC-20260325-0010\",\"montant\":126000}', '2026-03-25 12:08:03'),
(100, 1, 'CREDIT_PAYMENT', 'Paiement crédit FAC-20260312-0007 : 10000 XOF', '41.138.99.79', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '{\"id_vente\":7,\"montant\":10000}', '2026-03-25 12:09:49'),
(101, 1, 'connexion', 'Connexion réussie de Elbrahms', '102.180.20.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', NULL, '2026-03-25 13:16:26'),
(102, 1, 'VENTE', 'Nouvelle vente créée: FAC-20260325-0011 (10000 XOF)', '102.180.20.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '{\"id_vente\":\"11\",\"numero_facture\":\"FAC-20260325-0011\",\"montant\":10000}', '2026-03-25 13:20:51'),
(103, 1, 'VENTE', 'Nouvelle vente créée: FAC-20260325-0012 (5000 XOF)', '102.180.20.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '{\"id_vente\":\"12\",\"numero_facture\":\"FAC-20260325-0012\",\"montant\":5000}', '2026-03-25 13:25:01'),
(104, 1, 'connexion', 'Connexion réussie de Elbrahms', '102.180.20.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', NULL, '2026-03-25 14:45:48'),
(105, 1, 'connexion', 'Connexion réussie de Elbrahms', '121.159.128.237', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', NULL, '2026-03-26 07:54:32'),
(106, 1, 'CAISSE_OUVERTURE', 'Ouverture caisse avec 0.00 XOF', '121.159.128.237', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '{\"id_caisse\":\"1\",\"solde_ouverture\":0}', '2026-03-26 07:58:21'),
(107, 1, 'connexion', 'Connexion réussie de Elbrahms', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', NULL, '2026-03-26 13:26:51'),
(108, 1, 'VENTE', 'Nouvelle vente créée: FAC-20260326-0013 (150000 XOF)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '{\"id_vente\":\"13\",\"numero_facture\":\"FAC-20260326-0013\",\"montant\":150000}', '2026-03-26 13:50:43'),
(109, 1, 'connexion', 'Connexion réussie de Elbrahms', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', NULL, '2026-03-27 10:25:54'),
(110, 1, 'CAISSE_FERMETURE', 'Fermeture caisse — théorique: 150,000.00 | réel: 150,000.00 | écart: 0.00 XOF', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', '{\"id_caisse\":1,\"ecart\":0}', '2026-03-27 10:32:01');

-- --------------------------------------------------------

--
-- Structure de la table `mouvements`
--

DROP TABLE IF EXISTS `mouvements`;
CREATE TABLE IF NOT EXISTS `mouvements` (
  `id_mouvement` int NOT NULL AUTO_INCREMENT,
  `id_produit` int NOT NULL,
  `id_utilisateur` int NOT NULL,
  `type_mouvement` enum('entree','sortie','ajustement','vente') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantite` int NOT NULL,
  `prix_unitaire` decimal(15,2) DEFAULT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Numéro facture ou bon',
  `motif` text COLLATE utf8mb4_unicode_ci,
  `date_mouvement` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_mouvement`),
  KEY `idx_produit` (`id_produit`),
  KEY `idx_utilisateur` (`id_utilisateur`),
  KEY `idx_date` (`date_mouvement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mouvements_caisse`
--

DROP TABLE IF EXISTS `mouvements_caisse`;
CREATE TABLE IF NOT EXISTS `mouvements_caisse` (
  `id_mouvement` int NOT NULL AUTO_INCREMENT,
  `id_caisse` int NOT NULL,
  `type_mouvement` enum('ouverture','vente_especes','remboursement_especes','entree_manuelle','depense','sortie_manuelle','fermeture') COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(15,2) NOT NULL COMMENT 'Toujours positif; le signe est donné par type_mouvement',
  `sens` enum('entree','sortie') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ex: numéro de facture',
  `id_utilisateur` int NOT NULL,
  `date_mouvement` datetime NOT NULL,
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_mouvement`),
  KEY `fk_mvt_user` (`id_utilisateur`),
  KEY `idx_mvt_caisse_date` (`id_caisse`,`date_mouvement`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `mouvements_caisse`
--

INSERT INTO `mouvements_caisse` (`id_mouvement`, `id_caisse`, `type_mouvement`, `montant`, `sens`, `description`, `reference`, `id_utilisateur`, `date_mouvement`, `date_creation`) VALUES
(1, 1, 'ouverture', 0.00, 'entree', 'Ouverture de caisse', NULL, 1, '2026-03-26 08:58:21', '2026-03-26 06:58:21'),
(2, 1, 'vente_especes', 150000.00, 'entree', 'Vente FAC-20260326-0013', 'FAC-20260326-0013', 1, '2026-03-26 12:50:43', '2026-03-26 12:50:43'),
(3, 1, 'fermeture', 150000.00, 'sortie', 'Fermeture de caisse — solde réel : 150,000.00 XOF', NULL, 1, '2026-03-27 09:32:01', '2026-03-27 09:32:01');

-- --------------------------------------------------------

--
-- Structure de la table `mouvements_stock`
--

DROP TABLE IF EXISTS `mouvements_stock`;
CREATE TABLE IF NOT EXISTS `mouvements_stock` (
  `id_mouvement` int NOT NULL AUTO_INCREMENT,
  `id_produit` int NOT NULL COMMENT 'Produit concerné',
  `id_depot_source` int DEFAULT NULL COMMENT 'Dépôt source (pour transferts)',
  `id_depot_destination` int DEFAULT NULL COMMENT 'Dépôt destination',
  `id_fournisseur` int DEFAULT NULL COMMENT 'Fournisseur (pour entrées)',
  `type_mouvement` enum('entree','sortie','ajustement','retour','transfert','inventaire','perte') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantite` int NOT NULL COMMENT 'Quantité du mouvement',
  `quantite_avant` int NOT NULL COMMENT 'Stock avant le mouvement',
  `quantite_apres` int NOT NULL COMMENT 'Stock après le mouvement',
  `cout_unitaire` decimal(15,2) DEFAULT NULL COMMENT 'Coût unitaire d''achat',
  `cout_total` decimal(15,2) DEFAULT NULL COMMENT 'Coût total du mouvement',
  `id_vente` int DEFAULT NULL COMMENT 'Référence vente si sortie',
  `id_utilisateur` int NOT NULL COMMENT 'Utilisateur qui a fait l''opération',
  `motif` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Raison du mouvement',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `date_mouvement` datetime DEFAULT CURRENT_TIMESTAMP,
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
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique des mouvements de stock';

--
-- Déchargement des données de la table `mouvements_stock`
--

INSERT INTO `mouvements_stock` (`id_mouvement`, `id_produit`, `id_depot_source`, `id_depot_destination`, `id_fournisseur`, `type_mouvement`, `quantite`, `quantite_avant`, `quantite_apres`, `cout_unitaire`, `cout_total`, `id_vente`, `id_utilisateur`, `motif`, `notes`, `date_mouvement`) VALUES
(1, 1, 1, NULL, 2, 'entree', 10, 0, 10, NULL, NULL, NULL, 1, 'Stock initial lors de la création du produit', NULL, '2026-03-11 00:28:05'),
(2, 1, 1, NULL, 2, 'entree', 100, 10, 110, 1500.00, 150000.00, NULL, 1, 'rupture', NULL, '2026-03-11 00:29:10'),
(3, 1, NULL, NULL, NULL, 'sortie', 1, 110, 109, NULL, NULL, NULL, 1, 'Vente FAC-20260311-0001', NULL, '2026-03-11 01:29:32'),
(4, 2, 1, NULL, 1, 'entree', 10, 0, 10, NULL, NULL, NULL, 1, 'Stock initial lors de la création du produit', NULL, '2026-03-11 00:32:56'),
(5, 3, 1, NULL, 3, 'entree', 5, 0, 5, NULL, NULL, NULL, 1, 'Achat', NULL, '2026-03-11 10:08:45'),
(6, 3, NULL, NULL, NULL, 'sortie', 1, 5, 4, NULL, NULL, NULL, 1, 'Vente FAC-20260311-0002', NULL, '2026-03-11 09:11:43'),
(7, 1, NULL, NULL, NULL, 'sortie', 1, 109, 108, NULL, NULL, NULL, 1, 'Vente FAC-20260311-0002', NULL, '2026-03-11 09:11:43'),
(8, 3, NULL, NULL, NULL, 'sortie', 1, 4, 3, NULL, NULL, NULL, 1, 'Vente FAC-20260311-0003', NULL, '2026-03-11 09:22:48'),
(9, 2, NULL, NULL, NULL, 'sortie', 2, 10, 8, NULL, NULL, NULL, 1, 'Vente FAC-20260311-0004', NULL, '2026-03-11 09:31:49'),
(10, 5, 1, NULL, 2, 'entree', 10, 0, 10, NULL, NULL, NULL, 1, 'Stock initial lors de la création du produit', NULL, '2026-03-11 11:37:23'),
(11, 6, 1, NULL, 2, 'entree', 10, 0, 10, NULL, NULL, NULL, 1, 'Stock initial lors de la création du produit', NULL, '2026-03-11 11:42:35'),
(12, 7, 1, NULL, 2, 'entree', 50, 0, 50, NULL, NULL, NULL, 1, 'Stock initial lors de la création du produit', NULL, '2026-03-11 11:43:25'),
(13, 5, NULL, NULL, NULL, 'sortie', 5, 10, 5, NULL, NULL, NULL, 1, 'Vente FAC-20260311-0005', NULL, '2026-03-11 12:59:24'),
(14, 3, NULL, NULL, NULL, 'sortie', 1, 3, 2, NULL, NULL, NULL, 1, 'Vente FAC-20260312-0006', NULL, '2026-03-12 16:51:16'),
(15, 1, NULL, NULL, NULL, 'sortie', 1, 108, 107, NULL, NULL, NULL, 1, 'Vente FAC-20260312-0006', NULL, '2026-03-12 16:51:16'),
(16, 3, NULL, NULL, NULL, 'sortie', 1, 2, 1, NULL, NULL, NULL, 1, 'Vente FAC-20260312-0007', NULL, '2026-03-12 16:51:22'),
(17, 1, NULL, NULL, NULL, 'sortie', 1, 107, 106, NULL, NULL, NULL, 1, 'Vente FAC-20260312-0007', NULL, '2026-03-12 16:51:22'),
(18, 8, 1, NULL, 1, 'entree', 4, 0, 4, NULL, NULL, NULL, 1, 'Stock initial lors de la création du produit', NULL, '2026-03-13 18:02:51'),
(19, 8, NULL, NULL, NULL, 'sortie', 1, 4, 3, NULL, NULL, NULL, 1, 'Vente FAC-20260313-0008', NULL, '2026-03-13 19:05:20'),
(20, 7, NULL, NULL, NULL, 'sortie', 1, 50, 49, NULL, NULL, NULL, 1, 'Vente FAC-20260313-0008', NULL, '2026-03-13 19:05:20'),
(21, 7, NULL, NULL, NULL, 'sortie', 3, 49, 46, NULL, NULL, NULL, 1, 'Vente FAC-20260324-0009', NULL, '2026-03-24 14:04:39'),
(22, 7, NULL, NULL, NULL, 'sortie', 1, 46, 45, NULL, NULL, NULL, 1, 'Vente FAC-20260325-0010', NULL, '2026-03-25 13:08:03'),
(23, 3, NULL, NULL, NULL, 'sortie', 1, 1, 0, NULL, NULL, NULL, 1, 'Vente FAC-20260325-0010', NULL, '2026-03-25 13:08:03'),
(24, 9, 1, NULL, 1, 'entree', 1000, 0, 1000, NULL, NULL, NULL, 1, 'Stock initial lors de la création du produit', NULL, '2026-03-25 13:19:51'),
(25, 9, NULL, NULL, NULL, 'sortie', 1, 1000, 999, NULL, NULL, NULL, 1, 'Vente FAC-20260325-0011', NULL, '2026-03-25 14:20:51'),
(26, 7, 1, NULL, 1, 'entree', 5, 50, 55, 750.00, 3750.00, NULL, 1, 'LLL', NULL, '2026-03-25 13:23:32'),
(27, 7, NULL, NULL, NULL, 'sortie', 5, 55, 50, NULL, NULL, NULL, 1, 'Vente FAC-20260325-0012', NULL, '2026-03-25 14:25:01'),
(28, 8, NULL, NULL, NULL, 'sortie', 1, 3, 2, NULL, NULL, NULL, 1, 'Vente FAC-20260326-0013', NULL, '2026-03-26 12:50:43');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id_notification` int NOT NULL AUTO_INCREMENT,
  `type_notification` enum('stock_faible','stock_critique','rupture_stock','vente_importante','systeme') COLLATE utf8mb4_unicode_ci NOT NULL,
  `titre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_produit` int DEFAULT NULL COMMENT 'Produit concerné si applicable',
  `niveau_urgence` enum('info','avertissement','urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'info',
  `est_lue` tinyint(1) DEFAULT '0' COMMENT '0=Non lue, 1=Lue',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_notification`),
  KEY `idx_lue` (`est_lue`),
  KEY `idx_type` (`type_notification`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notifications système';

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id_notification`, `type_notification`, `titre`, `message`, `id_produit`, `niveau_urgence`, `est_lue`, `date_creation`) VALUES
(1, 'systeme', 'Bienvenue !', 'Votre système de gestion de stock a été configuré avec succès. Vous pouvez maintenant commencer à ajouter vos produits et effectuer vos ventes.', NULL, 'info', 0, '2026-03-11 00:39:54');

-- --------------------------------------------------------

--
-- Structure de la table `paiements_credit`
--

DROP TABLE IF EXISTS `paiements_credit`;
CREATE TABLE IF NOT EXISTS `paiements_credit` (
  `id_paiement` int NOT NULL AUTO_INCREMENT,
  `id_vente` int NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `mode_paiement` enum('especes','carte','mobile_money','cheque') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'especes',
  `notes` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `id_utilisateur` int NOT NULL,
  `date_paiement` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_paiement`),
  KEY `fk_pc_utilisateur` (`id_utilisateur`),
  KEY `idx_pc_id_vente` (`id_vente`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `paiements_credit`
--

INSERT INTO `paiements_credit` (`id_paiement`, `id_vente`, `montant`, `mode_paiement`, `notes`, `id_utilisateur`, `date_paiement`) VALUES
(1, 5, 10000.00, 'especes', 'Acompte initial', 1, '2026-03-11 12:59:24'),
(2, 5, 10000.00, 'mobile_money', '73895455', 1, '2026-03-11 14:03:44'),
(3, 6, 100000.00, 'especes', 'Acompte initial', 1, '2026-03-12 16:51:16'),
(4, 7, 100000.00, 'especes', 'Acompte initial', 1, '2026-03-12 16:51:22'),
(5, 8, 50000.00, 'especes', 'Acompte initial', 1, '2026-03-13 19:05:20'),
(6, 10, 20000.00, 'especes', 'Acompte initial', 1, '2026-03-25 13:08:03'),
(7, 7, 10000.00, 'especes', '', 1, '2026-03-25 13:09:48');

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

DROP TABLE IF EXISTS `produits`;
CREATE TABLE IF NOT EXISTS `produits` (
  `id_produit` int NOT NULL AUTO_INCREMENT,
  `code_produit` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Code/Référence unique du produit',
  `nom_produit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom du produit',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT 'Description détaillée',
  `id_categorie` int DEFAULT NULL COMMENT 'Catégorie du produit',
  `id_fournisseur_principal` int DEFAULT NULL COMMENT 'Fournisseur principal du produit',
  `prix_achat` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Prix d''achat (VISIBLE ADMIN SEULEMENT)',
  `prix_vente` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Prix de vente recommandé',
  `prix_vente_min` decimal(15,2) DEFAULT NULL COMMENT 'Prix de vente minimum autorisé',
  `quantite_stock` int NOT NULL DEFAULT '0' COMMENT 'Quantité actuelle en stock',
  `seuil_alerte` int DEFAULT '10' COMMENT 'Seuil pour alerte stock faible',
  `seuil_critique` int DEFAULT '5' COMMENT 'Seuil critique (alerte rouge)',
  `unite_mesure` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'pièce' COMMENT 'Unité (pièce, kg, litre, etc.)',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Image du produit',
  `code_barre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Code-barres pour scanner',
  `emplacement` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Emplacement dans le magasin',
  `date_entree` date DEFAULT NULL COMMENT 'Date dernière entrée en stock',
  `date_derniere_vente` datetime DEFAULT NULL COMMENT 'Date de la dernière vente',
  `nombre_ventes` int DEFAULT '0' COMMENT 'Nombre total de ventes',
  `est_actif` tinyint(1) DEFAULT '1' COMMENT '0=Inactif, 1=Actif',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_produit`),
  UNIQUE KEY `code_produit` (`code_produit`),
  KEY `idx_categorie` (`id_categorie`),
  KEY `idx_stock` (`quantite_stock`),
  KEY `idx_code` (`code_produit`),
  KEY `idx_actif` (`est_actif`),
  KEY `idx_produits_stock_actif` (`quantite_stock`,`est_actif`),
  KEY `idx_fournisseur_principal` (`id_fournisseur_principal`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Produits en stock avec gestion des alertes';

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id_produit`, `code_produit`, `nom_produit`, `description`, `id_categorie`, `id_fournisseur_principal`, `prix_achat`, `prix_vente`, `prix_vente_min`, `quantite_stock`, `seuil_alerte`, `seuil_critique`, `unite_mesure`, `image`, `code_barre`, `emplacement`, `date_entree`, `date_derniere_vente`, `nombre_ventes`, `est_actif`, `date_creation`, `date_modification`) VALUES
(1, '5888888', 'Produit test', '', 5, 2, 1500.00, 2000.00, NULL, 106, 5, 2, 'pièce', NULL, NULL, NULL, NULL, NULL, 0, 1, '2026-03-11 00:28:05', '2026-03-12 14:51:22'),
(2, '58888886', 'Produit test1', '', 1, 1, 1000.00, 2500.00, NULL, 8, 5, 2, 'pièce', NULL, NULL, NULL, NULL, NULL, 0, 1, '2026-03-11 00:32:56', '2026-03-11 09:31:49'),
(3, NULL, 'Imprimente', '', 1, 1, 90000.00, 125000.00, NULL, 0, 5, 2, 'pièce', NULL, NULL, NULL, NULL, NULL, 0, 1, '2026-03-11 10:03:26', '2026-03-25 11:08:03'),
(4, NULL, 'Produit X', '', 5, 2, 1000.00, 1500.00, NULL, 0, 5, 2, 'pièce', NULL, NULL, NULL, NULL, NULL, 0, 1, '2026-03-11 10:05:36', '2026-03-11 09:05:36'),
(5, '58888889', 'Produit test 7', '', 6, 2, 1500.00, 8000.00, NULL, 5, 5, 2, 'pièce', NULL, NULL, NULL, NULL, NULL, 0, 1, '2026-03-11 11:37:23', '2026-03-11 10:59:24'),
(6, '588888844', 'Produit test44', '', 5, 2, 2000.00, 3000.00, NULL, 10, 5, 2, 'pièce', NULL, NULL, NULL, NULL, NULL, 0, 1, '2026-03-11 11:42:35', '2026-03-11 10:42:35'),
(7, '5888888447', 'BAZIN', '', 4, 2, 500.00, 1000.00, NULL, 50, 5, 2, 'pièce', NULL, NULL, NULL, NULL, NULL, 0, 1, '2026-03-11 11:43:25', '2026-03-25 12:25:01'),
(8, NULL, 'DFFFFF', '', 7, 1, 10000.00, 150000.00, NULL, 2, 5, 2, 'pièce', NULL, NULL, NULL, NULL, NULL, 0, 1, '2026-03-13 18:02:51', '2026-03-26 12:50:43'),
(9, NULL, 'CONFECTION', '', 6, 1, 0.00, 5000.00, NULL, 999, 5, 2, 'pièce', NULL, NULL, NULL, NULL, NULL, 0, 1, '2026-03-25 13:19:51', '2026-03-25 12:20:51');

-- --------------------------------------------------------

--
-- Structure de la table `stock_par_depot`
--

DROP TABLE IF EXISTS `stock_par_depot`;
CREATE TABLE IF NOT EXISTS `stock_par_depot` (
  `id_stock` int NOT NULL AUTO_INCREMENT,
  `id_produit` int NOT NULL COMMENT 'Référence au produit',
  `id_depot` int NOT NULL COMMENT 'Référence au dépôt',
  `quantite` int NOT NULL DEFAULT '0' COMMENT 'Quantité dans ce dépôt',
  `seuil_alerte` int DEFAULT '10' COMMENT 'Seuil d''alerte pour ce dépôt',
  `date_derniere_maj` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_stock`),
  UNIQUE KEY `unique_produit_depot` (`id_produit`,`id_depot`),
  KEY `idx_id_produit` (`id_produit`),
  KEY `idx_id_depot` (`id_depot`),
  KEY `idx_quantite` (`quantite`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stock par emplacement (multi-localisation)';

--
-- Déchargement des données de la table `stock_par_depot`
--

INSERT INTO `stock_par_depot` (`id_stock`, `id_produit`, `id_depot`, `quantite`, `seuil_alerte`, `date_derniere_maj`) VALUES
(1, 1, 1, 110, 5, '2026-03-11 00:29:10'),
(2, 2, 1, 10, 5, '2026-03-11 00:32:56'),
(3, 3, 1, 5, 5, '2026-03-11 10:08:45'),
(4, 4, 1, 0, 5, '2026-03-11 10:05:36'),
(5, 5, 1, 10, 5, '2026-03-11 11:37:23'),
(6, 6, 1, 10, 5, '2026-03-11 11:42:35'),
(7, 7, 1, 55, 5, '2026-03-25 13:23:32'),
(8, 8, 1, 4, 5, '2026-03-13 18:02:51'),
(9, 9, 1, 1000, 5, '2026-03-25 13:19:51');

--
-- Déclencheurs `stock_par_depot`
--
DROP TRIGGER IF EXISTS `after_stock_par_depot_delete`;
DELIMITER $$
CREATE TRIGGER `after_stock_par_depot_delete` AFTER DELETE ON `stock_par_depot` FOR EACH ROW BEGIN
    -- Recalculer le stock total du produit
    UPDATE produits 
    SET quantite_stock = (
        SELECT COALESCE(SUM(quantite), 0) 
        FROM stock_par_depot 
        WHERE id_produit = OLD.id_produit
    )
    WHERE id_produit = OLD.id_produit;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `after_stock_par_depot_insert`;
DELIMITER $$
CREATE TRIGGER `after_stock_par_depot_insert` AFTER INSERT ON `stock_par_depot` FOR EACH ROW BEGIN
    -- Recalculer le stock total du produit
    UPDATE produits 
    SET quantite_stock = (
        SELECT COALESCE(SUM(quantite), 0) 
        FROM stock_par_depot 
        WHERE id_produit = NEW.id_produit
    )
    WHERE id_produit = NEW.id_produit;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `after_stock_par_depot_update`;
DELIMITER $$
CREATE TRIGGER `after_stock_par_depot_update` AFTER UPDATE ON `stock_par_depot` FOR EACH ROW BEGIN
    -- Recalculer le stock total du produit
    UPDATE produits 
    SET quantite_stock = (
        SELECT COALESCE(SUM(quantite), 0) 
        FROM stock_par_depot 
        WHERE id_produit = NEW.id_produit
    )
    WHERE id_produit = NEW.id_produit;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id_utilisateur` int NOT NULL AUTO_INCREMENT,
  `nom_complet` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom complet de l''utilisateur',
  `login` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Identifiant de connexion (unique)',
  `mot_de_passe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mot de passe hashé (password_hash)',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Adresse email',
  `telephone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Numéro de téléphone',
  `niveau_acces` tinyint(1) NOT NULL DEFAULT '2' COMMENT '1=Admin, 2=Vendeur',
  `id_depot` int DEFAULT NULL COMMENT 'Dépôt assigné (obligatoire pour les caissiers/vendeurs)',
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Photo de profil',
  `est_actif` tinyint(1) DEFAULT '1' COMMENT '0=Inactif, 1=Actif',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création du compte',
  `date_derniere_connexion` datetime DEFAULT NULL COMMENT 'Dernière connexion',
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `login` (`login`),
  KEY `idx_login` (`login`),
  KEY `idx_niveau` (`niveau_acces`),
  KEY `fk_user_depot` (`id_depot`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Utilisateurs du système avec permissions';

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_utilisateur`, `nom_complet`, `login`, `mot_de_passe`, `email`, `telephone`, `niveau_acces`, `id_depot`, `photo`, `est_actif`, `date_creation`, `date_derniere_connexion`, `date_modification`) VALUES
(1, 'Elbrahms', 'elbrahms', '$2y$10$UOMGrMSeEwMxYdsZ2f6oK.2Lmxs3uYwuAtxe6d53jII5o5P4dUo9G', 'elbrahms@fite-ne.com', NULL, 1, NULL, NULL, 1, '2026-03-11 00:39:54', '2026-03-27 09:25:54', '2026-03-27 09:25:54');

-- --------------------------------------------------------

--
-- Structure de la table `ventes`
--

DROP TABLE IF EXISTS `ventes`;
CREATE TABLE IF NOT EXISTS `ventes` (
  `id_vente` int NOT NULL AUTO_INCREMENT,
  `numero_facture` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Numéro unique de la facture',
  `id_client` int DEFAULT NULL COMMENT 'Client (NULL = vente comptoir)',
  `id_vendeur` int NOT NULL COMMENT 'Vendeur/Caissier qui a effectué la vente',
  `montant_total` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Montant total de la vente',
  `montant_ht` decimal(10,2) DEFAULT '0.00',
  `montant_remise` decimal(15,2) DEFAULT '0.00' COMMENT 'Remise accordée',
  `montant_tva` decimal(15,2) DEFAULT '0.00' COMMENT 'Montant TVA',
  `montant_paye` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Montant payé par le client',
  `montant_rendu` decimal(15,2) DEFAULT '0.00' COMMENT 'Monnaie rendue',
  `mode_paiement` enum('especes','carte','mobile_money','cheque','credit') COLLATE utf8mb4_unicode_ci DEFAULT 'especes',
  `date_echeance` date DEFAULT NULL,
  `statut` enum('en_cours','validee','annulee') COLLATE utf8mb4_unicode_ci DEFAULT 'validee',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT 'Notes ou observations',
  `date_vente` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Date et heure de la vente',
  `date_modification` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='En-têtes des ventes (factures)';

--
-- Déchargement des données de la table `ventes`
--

INSERT INTO `ventes` (`id_vente`, `numero_facture`, `id_client`, `id_vendeur`, `montant_total`, `montant_ht`, `montant_remise`, `montant_tva`, `montant_paye`, `montant_rendu`, `mode_paiement`, `date_echeance`, `statut`, `notes`, `date_vente`, `date_modification`) VALUES
(1, 'FAC-20260311-0001', NULL, 1, 2000.00, 1724.14, 0.00, 275.86, 0.00, 0.00, 'especes', NULL, 'validee', '', '2026-03-11 01:29:32', '2026-03-10 23:29:32'),
(2, 'FAC-20260311-0002', NULL, 1, 127000.00, 109482.76, 0.00, 17517.24, 0.00, 0.00, 'mobile_money', NULL, 'validee', '', '2026-03-11 09:11:43', '2026-03-11 09:11:43'),
(3, 'FAC-20260311-0003', 2, 1, 125000.00, 107758.62, 0.00, 17241.38, 0.00, 0.00, 'especes', NULL, 'validee', '', '2026-03-11 09:22:48', '2026-03-11 09:22:48'),
(4, 'FAC-20260311-0004', NULL, 1, 5000.00, 5000.00, 0.00, 0.00, 0.00, 0.00, 'credit', NULL, 'validee', '', '2026-03-11 09:31:49', '2026-03-11 09:31:49'),
(5, 'FAC-20260311-0005', 2, 1, 40000.00, 40000.00, 0.00, 0.00, 20000.00, 0.00, 'credit', '2026-04-01', 'validee', '', '2026-03-11 12:59:24', '2026-03-11 12:03:44'),
(6, 'FAC-20260312-0006', 2, 1, 127000.00, 127000.00, 0.00, 0.00, 100000.00, 0.00, 'credit', '2026-03-14', 'validee', '', '2026-03-12 16:51:16', '2026-03-12 14:51:16'),
(7, 'FAC-20260312-0007', 2, 1, 127000.00, 127000.00, 0.00, 0.00, 110000.00, 0.00, 'credit', '2026-03-14', 'validee', '', '2026-03-12 16:51:22', '2026-03-25 11:09:48'),
(8, 'FAC-20260313-0008', 2, 1, 151000.00, 151000.00, 0.00, 0.00, 50000.00, 0.00, 'credit', '2026-03-25', 'validee', '', '2026-03-13 19:05:20', '2026-03-13 17:05:20'),
(9, 'FAC-20260324-0009', 2, 1, 3000.00, 3000.00, 0.00, 0.00, 0.00, 0.00, 'mobile_money', NULL, 'validee', '', '2026-03-24 14:04:39', '2026-03-24 12:04:39'),
(10, 'FAC-20260325-0010', 2, 1, 126000.00, 126000.00, 0.00, 0.00, 20000.00, 0.00, 'credit', '2026-04-01', 'validee', '', '2026-03-25 13:08:02', '2026-03-25 11:08:02'),
(11, 'FAC-20260325-0011', 2, 1, 10000.00, 10000.00, 0.00, 0.00, 0.00, 0.00, 'especes', NULL, 'validee', '', '2026-03-25 14:20:51', '2026-03-25 12:20:51'),
(12, 'FAC-20260325-0012', NULL, 1, 5000.00, 5000.00, 0.00, 0.00, 0.00, 0.00, 'especes', NULL, 'validee', '', '2026-03-25 14:25:01', '2026-03-25 12:25:01'),
(13, 'FAC-20260326-0013', NULL, 1, 150000.00, 150000.00, 0.00, 0.00, 0.00, 0.00, 'especes', NULL, 'validee', '', '2026-03-26 12:50:43', '2026-03-26 12:50:43');

--
-- Déclencheurs `ventes`
--
DROP TRIGGER IF EXISTS `before_vente_insert`;
DELIMITER $$
CREATE TRIGGER `before_vente_insert` BEFORE INSERT ON `ventes` FOR EACH ROW BEGIN
    IF NEW.numero_facture IS NULL OR NEW.numero_facture = '' THEN
        SET NEW.numero_facture = CONCAT('FAC', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD((SELECT COALESCE(MAX(id_vente), 0) + 1 FROM ventes), 6, '0'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `ventes_details`
--

DROP TABLE IF EXISTS `ventes_details`;
CREATE TABLE IF NOT EXISTS `ventes_details` (
  `id_detail` int NOT NULL AUTO_INCREMENT,
  `id_vente` int NOT NULL COMMENT 'Référence à la vente',
  `id_produit` int NOT NULL COMMENT 'Produit vendu',
  `nom_produit` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nom du produit (copie pour historique)',
  `quantite` int NOT NULL DEFAULT '1' COMMENT 'Quantité vendue',
  `prix_unitaire` decimal(15,2) NOT NULL COMMENT 'Prix unitaire de vente',
  `prix_achat_unitaire` decimal(15,2) NOT NULL COMMENT 'Prix d''achat (pour calcul bénéfice)',
  `prix_total` decimal(15,2) NOT NULL COMMENT 'Prix total de la ligne (quantité × prix)',
  `benefice_ligne` decimal(15,2) NOT NULL COMMENT 'Bénéfice sur cette ligne',
  `remise_ligne` decimal(15,2) DEFAULT '0.00' COMMENT 'Remise sur cette ligne',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_detail`),
  KEY `idx_vente` (`id_vente`),
  KEY `idx_produit` (`id_produit`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Détails des ventes (lignes de factures)';

--
-- Déclencheurs `ventes_details`
--
DROP TRIGGER IF EXISTS `after_vente_detail_insert`;
DELIMITER $$
CREATE TRIGGER `after_vente_detail_insert` AFTER INSERT ON `ventes_details` FOR EACH ROW BEGIN
    -- Diminuer le stock du produit
    UPDATE produits 
    SET quantite_stock = quantite_stock - NEW.quantite,
        date_derniere_vente = NOW(),
        nombre_ventes = nombre_ventes + NEW.quantite
    WHERE id_produit = NEW.id_produit;
    
    -- Créer une notification si stock faible
    IF (SELECT quantite_stock FROM produits WHERE id_produit = NEW.id_produit) <= 
       (SELECT seuil_critique FROM produits WHERE id_produit = NEW.id_produit) THEN
        INSERT INTO notifications (type_notification, titre, message, id_produit, niveau_urgence)
        SELECT 'stock_critique', 
               CONCAT('Stock critique: ', nom_produit),
               CONCAT('Le stock de ', nom_produit, ' est critique (', quantite_stock, ' restant)'),
               id_produit,
               'urgent'
        FROM produits WHERE id_produit = NEW.id_produit;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_inventaire_complet`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `vue_inventaire_complet`;
CREATE TABLE IF NOT EXISTS `vue_inventaire_complet` (
`code_produit` varchar(100)
,`id_depot` int
,`id_produit` int
,`nom_categorie` varchar(255)
,`nom_depot` varchar(255)
,`nom_produit` varchar(255)
,`prix_achat` decimal(15,2)
,`prix_vente` decimal(15,2)
,`quantite` int
,`seuil_alerte` int
,`statut_depot` varchar(7)
,`type_depot` enum('magasin','depot','entrepot','autre')
,`unite_mesure` varchar(50)
,`valeur_stock_achat` decimal(25,2)
,`valeur_stock_vente` decimal(25,2)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_mouvements_stock_detail`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `vue_mouvements_stock_detail`;
CREATE TABLE IF NOT EXISTS `vue_mouvements_stock_detail` (
`code_produit` varchar(100)
,`cout_total` decimal(15,2)
,`cout_unitaire` decimal(15,2)
,`date_mouvement` datetime
,`depot_destination` varchar(255)
,`depot_source` varchar(255)
,`id_mouvement` int
,`id_produit` int
,`motif` varchar(255)
,`nom_fournisseur` varchar(255)
,`nom_produit` varchar(255)
,`notes` text
,`numero_facture` varchar(50)
,`quantite` int
,`quantite_apres` int
,`quantite_avant` int
,`type_mouvement` enum('entree','sortie','ajustement','retour','transfert','inventaire','perte')
,`type_mouvement_libelle` varchar(14)
,`unite_mesure` varchar(50)
,`utilisateur` varchar(255)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_produits_alertes`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `vue_produits_alertes`;
CREATE TABLE IF NOT EXISTS `vue_produits_alertes` (
`code_produit` varchar(100)
,`date_entree` date
,`id_produit` int
,`niveau_alerte` varchar(8)
,`nom_categorie` varchar(255)
,`nom_produit` varchar(255)
,`quantite_stock` int
,`seuil_alerte` int
,`seuil_critique` int
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_statistiques_ventes`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `vue_statistiques_ventes`;
CREATE TABLE IF NOT EXISTS `vue_statistiques_ventes` (
`benefice_total` decimal(37,2)
,`chiffre_affaires` decimal(37,2)
,`date_vente` date
,`nombre_ventes` bigint
,`vendeur` varchar(255)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `vue_stock_global`
-- (Voir ci-dessous la vue réelle)
--
DROP VIEW IF EXISTS `vue_stock_global`;
CREATE TABLE IF NOT EXISTS `vue_stock_global` (
`code_produit` varchar(100)
,`est_actif` tinyint(1)
,`id_categorie` int
,`id_produit` int
,`nom_categorie` varchar(255)
,`nom_produit` varchar(255)
,`prix_achat` decimal(15,2)
,`prix_vente` decimal(15,2)
,`seuil_alerte` int
,`seuil_critique` int
,`statut_stock` varchar(8)
,`stock_total` decimal(32,0)
,`unite_mesure` varchar(50)
);

-- --------------------------------------------------------

--
-- Structure de la vue `vue_inventaire_complet`
--
DROP TABLE IF EXISTS `vue_inventaire_complet`;

DROP VIEW IF EXISTS `vue_inventaire_complet`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_inventaire_complet`  AS SELECT `p`.`id_produit` AS `id_produit`, `p`.`code_produit` AS `code_produit`, `p`.`nom_produit` AS `nom_produit`, `c`.`nom_categorie` AS `nom_categorie`, `d`.`id_depot` AS `id_depot`, `d`.`nom_depot` AS `nom_depot`, `d`.`type_depot` AS `type_depot`, `spd`.`quantite` AS `quantite`, `p`.`prix_achat` AS `prix_achat`, `p`.`prix_vente` AS `prix_vente`, (`spd`.`quantite` * `p`.`prix_achat`) AS `valeur_stock_achat`, (`spd`.`quantite` * `p`.`prix_vente`) AS `valeur_stock_vente`, `p`.`unite_mesure` AS `unite_mesure`, `spd`.`seuil_alerte` AS `seuil_alerte`, (case when (`spd`.`quantite` = 0) then 'rupture' when (`spd`.`quantite` <= `spd`.`seuil_alerte`) then 'alerte' else 'normal' end) AS `statut_depot` FROM (((`produits` `p` join `stock_par_depot` `spd` on((`p`.`id_produit` = `spd`.`id_produit`))) join `depots` `d` on((`spd`.`id_depot` = `d`.`id_depot`))) left join `categories` `c` on((`p`.`id_categorie` = `c`.`id_categorie`))) WHERE ((`p`.`est_actif` = 1) AND (`d`.`est_actif` = 1)) ORDER BY `p`.`nom_produit` ASC, `d`.`ordre_affichage` ASC, `d`.`nom_depot` ASC ;

-- --------------------------------------------------------

--
-- Structure de la vue `vue_mouvements_stock_detail`
--
DROP TABLE IF EXISTS `vue_mouvements_stock_detail`;

DROP VIEW IF EXISTS `vue_mouvements_stock_detail`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_mouvements_stock_detail`  AS SELECT `m`.`id_mouvement` AS `id_mouvement`, `m`.`type_mouvement` AS `type_mouvement`, `m`.`date_mouvement` AS `date_mouvement`, `p`.`id_produit` AS `id_produit`, `p`.`code_produit` AS `code_produit`, `p`.`nom_produit` AS `nom_produit`, `p`.`unite_mesure` AS `unite_mesure`, `m`.`quantite` AS `quantite`, `m`.`quantite_avant` AS `quantite_avant`, `m`.`quantite_apres` AS `quantite_apres`, `ds`.`nom_depot` AS `depot_source`, `dd`.`nom_depot` AS `depot_destination`, `f`.`nom_fournisseur` AS `nom_fournisseur`, `m`.`cout_unitaire` AS `cout_unitaire`, `m`.`cout_total` AS `cout_total`, `m`.`motif` AS `motif`, `m`.`notes` AS `notes`, `u`.`nom_complet` AS `utilisateur`, `v`.`numero_facture` AS `numero_facture`, (case `m`.`type_mouvement` when 'entree' then 'Entrée stock' when 'sortie' then 'Sortie (Vente)' when 'transfert' then 'Transfert' when 'ajustement' then 'Ajustement' when 'inventaire' then 'Inventaire' when 'perte' then 'Perte/Casse' when 'retour' then 'Retour' else 'Autre' end) AS `type_mouvement_libelle` FROM ((((((`mouvements_stock` `m` join `produits` `p` on((`m`.`id_produit` = `p`.`id_produit`))) left join `depots` `ds` on((`m`.`id_depot_source` = `ds`.`id_depot`))) left join `depots` `dd` on((`m`.`id_depot_destination` = `dd`.`id_depot`))) left join `fournisseurs` `f` on((`m`.`id_fournisseur` = `f`.`id_fournisseur`))) left join `utilisateurs` `u` on((`m`.`id_utilisateur` = `u`.`id_utilisateur`))) left join `ventes` `v` on((`m`.`id_vente` = `v`.`id_vente`))) ORDER BY `m`.`date_mouvement` DESC, `m`.`id_mouvement` DESC ;

-- --------------------------------------------------------

--
-- Structure de la vue `vue_produits_alertes`
--
DROP TABLE IF EXISTS `vue_produits_alertes`;

DROP VIEW IF EXISTS `vue_produits_alertes`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_produits_alertes`  AS SELECT `p`.`id_produit` AS `id_produit`, `p`.`nom_produit` AS `nom_produit`, `p`.`code_produit` AS `code_produit`, `c`.`nom_categorie` AS `nom_categorie`, `p`.`quantite_stock` AS `quantite_stock`, `p`.`seuil_alerte` AS `seuil_alerte`, `p`.`seuil_critique` AS `seuil_critique`, (case when (`p`.`quantite_stock` = 0) then 'rupture' when (`p`.`quantite_stock` <= `p`.`seuil_critique`) then 'critique' when (`p`.`quantite_stock` <= `p`.`seuil_alerte`) then 'faible' else 'normal' end) AS `niveau_alerte`, `p`.`date_entree` AS `date_entree` FROM (`produits` `p` left join `categories` `c` on((`p`.`id_categorie` = `c`.`id_categorie`))) WHERE ((`p`.`est_actif` = 1) AND (`p`.`quantite_stock` <= `p`.`seuil_alerte`)) ;

-- --------------------------------------------------------

--
-- Structure de la vue `vue_statistiques_ventes`
--
DROP TABLE IF EXISTS `vue_statistiques_ventes`;

DROP VIEW IF EXISTS `vue_statistiques_ventes`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_statistiques_ventes`  AS SELECT cast(`v`.`date_vente` as date) AS `date_vente`, count(`v`.`id_vente`) AS `nombre_ventes`, sum(`v`.`montant_total`) AS `chiffre_affaires`, sum(`vd`.`benefice_ligne`) AS `benefice_total`, `u`.`nom_complet` AS `vendeur` FROM ((`ventes` `v` left join `ventes_details` `vd` on((`v`.`id_vente` = `vd`.`id_vente`))) left join `utilisateurs` `u` on((`v`.`id_vendeur` = `u`.`id_utilisateur`))) WHERE (`v`.`statut` = 'validee') GROUP BY cast(`v`.`date_vente` as date), `u`.`id_utilisateur` ;

-- --------------------------------------------------------

--
-- Structure de la vue `vue_stock_global`
--
DROP TABLE IF EXISTS `vue_stock_global`;

DROP VIEW IF EXISTS `vue_stock_global`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vue_stock_global`  AS SELECT `p`.`id_produit` AS `id_produit`, `p`.`code_produit` AS `code_produit`, `p`.`nom_produit` AS `nom_produit`, `p`.`id_categorie` AS `id_categorie`, `c`.`nom_categorie` AS `nom_categorie`, `p`.`prix_achat` AS `prix_achat`, `p`.`prix_vente` AS `prix_vente`, coalesce(sum(`spd`.`quantite`),0) AS `stock_total`, `p`.`seuil_alerte` AS `seuil_alerte`, `p`.`seuil_critique` AS `seuil_critique`, `p`.`unite_mesure` AS `unite_mesure`, `p`.`est_actif` AS `est_actif`, (case when (coalesce(sum(`spd`.`quantite`),0) = 0) then 'rupture' when (coalesce(sum(`spd`.`quantite`),0) <= `p`.`seuil_critique`) then 'critique' when (coalesce(sum(`spd`.`quantite`),0) <= `p`.`seuil_alerte`) then 'alerte' else 'normal' end) AS `statut_stock` FROM ((`produits` `p` left join `categories` `c` on((`p`.`id_categorie` = `c`.`id_categorie`))) left join `stock_par_depot` `spd` on((`p`.`id_produit` = `spd`.`id_produit`))) WHERE (`p`.`est_actif` = 1) GROUP BY `p`.`id_produit`, `p`.`code_produit`, `p`.`nom_produit`, `p`.`id_categorie`, `c`.`nom_categorie`, `p`.`prix_achat`, `p`.`prix_vente`, `p`.`seuil_alerte`, `p`.`seuil_critique`, `p`.`unite_mesure`, `p`.`est_actif` ;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `caisse`
--
ALTER TABLE `caisse`
  ADD CONSTRAINT `fk_caisse_user` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `depenses`
--
ALTER TABLE `depenses`
  ADD CONSTRAINT `fk_dep_caisse` FOREIGN KEY (`id_caisse`) REFERENCES `caisse` (`id_caisse`),
  ADD CONSTRAINT `fk_dep_categorie` FOREIGN KEY (`id_categorie`) REFERENCES `categories_depenses` (`id_categorie`),
  ADD CONSTRAINT `fk_dep_user` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `mouvements_caisse`
--
ALTER TABLE `mouvements_caisse`
  ADD CONSTRAINT `fk_mvt_caisse` FOREIGN KEY (`id_caisse`) REFERENCES `caisse` (`id_caisse`),
  ADD CONSTRAINT `fk_mvt_user` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`);

--
-- Contraintes pour la table `paiements_credit`
--
ALTER TABLE `paiements_credit`
  ADD CONSTRAINT `fk_pc_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`),
  ADD CONSTRAINT `fk_pc_vente` FOREIGN KEY (`id_vente`) REFERENCES `ventes` (`id_vente`) ON DELETE CASCADE;

--
-- Contraintes pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `fk_user_depot` FOREIGN KEY (`id_depot`) REFERENCES `depots` (`id_depot`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
