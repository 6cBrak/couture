<?php
session_start();
require_once("connexion.php");

$nom = htmlspecialchars(trim(addslashes($_POST['nom'])));
$prix_achat = htmlspecialchars(trim(addslashes($_POST['prix_achat'])));
$prix_vente = htmlspecialchars(trim(addslashes($_POST['prix_vente'])));
$quantite = htmlspecialchars(trim(addslashes($_POST['quantite'])));
$signaler = htmlspecialchars(trim(addslashes($_POST['signaler'])));

$req="insert into PRODUIT(TITRE_PRODUIT,PRIX_ACHAT_PRODUIT,PRIX_VENTE_PRODUIT,QUANTITE_PRODUIT,SEUIL_PRODUIT) values ('$nom',$prix_achat,$prix_vente,$quantite,$signaler)";
mysqli_query($connexion,$req);


echo 1;
  
mysqli_close($connexion);
?>