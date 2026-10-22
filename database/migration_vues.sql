-- ============================================================================
-- MIGRATION : remplacer les "fausses vues" (tables vides issues de l'ancien init.sql)
-- par de vraies vues. A lancer UNE FOIS sur une base déjà installée.
-- Sans danger si relancé. Ne touche à aucune donnée des tables produits/ventes/etc.
-- ============================================================================
SET NAMES utf8mb4;

-- Ces 4 objets sont des tables VIDES dans les anciennes installations
-- (sans effet s'ils sont déjà des vues ou s'ils n'existent pas)
DROP TABLE IF EXISTS `vue_produits_alertes`;
DROP TABLE IF EXISTS `vue_statistiques_ventes`;
DROP TABLE IF EXISTS `vue_stock_global`;
DROP TABLE IF EXISTS `vue_mouvements_stock_detail`;
CREATE OR REPLACE SQL SECURITY INVOKER VIEW `vue_produits_alertes` AS
SELECT
    p.id_produit,
    p.nom_produit,
    p.code_produit,
    c.nom_categorie,
    p.quantite_stock,
    p.seuil_alerte,
    p.seuil_critique,
    CASE
        WHEN p.quantite_stock = 0 THEN 'rupture'
        WHEN p.quantite_stock <= p.seuil_critique THEN 'critique'
        WHEN p.quantite_stock <= p.seuil_alerte THEN 'faible'
        ELSE 'normal'
    END AS niveau_alerte,
    p.date_entree
FROM produits p
LEFT JOIN categories c ON p.id_categorie = c.id_categorie
WHERE p.est_actif = 1 AND p.quantite_stock <= p.seuil_alerte;

CREATE OR REPLACE SQL SECURITY INVOKER VIEW `vue_statistiques_ventes` AS
SELECT
    CAST(v.date_vente AS DATE) AS date_vente,
    COUNT(v.id_vente) AS nombre_ventes,
    SUM(v.montant_total) AS chiffre_affaires,
    SUM(vd.benefice_ligne) AS benefice_total,
    u.nom_complet AS vendeur
FROM ventes v
LEFT JOIN ventes_details vd ON v.id_vente = vd.id_vente
LEFT JOIN utilisateurs u ON v.id_vendeur = u.id_utilisateur
WHERE v.statut = 'validee'
GROUP BY CAST(v.date_vente AS DATE), u.id_utilisateur;

CREATE OR REPLACE SQL SECURITY INVOKER VIEW `vue_stock_global` AS
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

CREATE OR REPLACE SQL SECURITY INVOKER VIEW `vue_inventaire_complet` AS
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

CREATE OR REPLACE SQL SECURITY INVOKER VIEW `vue_mouvements_stock_detail` AS
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
