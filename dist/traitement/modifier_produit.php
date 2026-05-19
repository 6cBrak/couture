<?php
session_start();
require_once("connexion.php");

    $id_produit = htmlspecialchars(trim(addslashes($_GET['id_produit'])));
    $nom = htmlspecialchars(trim(addslashes($_POST['nom'])));
    $prix_achat = htmlspecialchars(trim(addslashes($_POST['prix_achat'])));
    $prix_vente = htmlspecialchars(trim(addslashes($_POST['prix_vente'])));
    $quantite = htmlspecialchars(trim(addslashes($_POST['quantite'])));
    $signaler = htmlspecialchars(trim(addslashes($_POST['signaler'])));

    $req="update PRODUIT set TITRE_PRODUIT = '$nom',PRIX_ACHAT_PRODUIT = $prix_achat,PRIX_VENTE_PRODUIT = $prix_vente,QUANTITE_PRODUIT = $quantite,SEUIL_PRODUIT = $signaler where ID_PRODUIT = $id_produit ";
    mysqli_query($connexion,$req);
    
    
    echo 1;
  
mysqli_close($connexion);
?>