<?php
/**
 * ============================================================================
 * FICHIER CONNEXION (ANCIEN - GARDÉ POUR COMPATIBILITÉ)
 * ============================================================================
 * 
 * Ce fichier charge la protection des pages et la connexion moderne
 * Il est conservé pour ne pas casser les anciennes inclusions
 * 
 * ⚠️ POUR LES NOUVEAUX FICHIERS : Utiliser require_once('protection_pages.php');
 * 
 * ============================================================================
 */

// Charger la protection des pages (qui charge aussi la config et database)
require_once('protection_pages.php');

// Variables de compatibilité avec l'ancien système
// (pour ne pas casser le code existant)
$ET100 = $config; // $config vient de protection_pages.php
?>
