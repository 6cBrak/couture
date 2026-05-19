<?php
session_start();
require_once("connexion.php");

$id_produit = htmlspecialchars(trim(addslashes($_GET['id_produit'])));
$prix_vente = htmlspecialchars(trim(addslashes($_GET['prix_vente'])));
$prix_achat = htmlspecialchars(trim(addslashes($_GET['prix_achat'])));
$stock = htmlspecialchars(trim(addslashes($_GET['stock'])));
$quantite = htmlspecialchars(trim(addslashes($_POST['quantite'])));

$id_utilisateur = $_SESSION['ID'];

$req = "select * from UTILISATEUR where ID_UTILISATEUR = $id_utilisateur ";
$rs = mysqli_query($connexion, $req);

$ET = mysqli_fetch_assoc($rs);

$vendeur = $ET['NOM'];

$req="insert into VENTE(QUANTITE_VENTE,PRIX_VENTE,PRIX_ACHAT,VENDEUR,DATE_VENTE,ID_PRODUIT) values ($quantite,$prix_vente,$prix_achat,'$vendeur',NOW(),$id_produit)";
mysqli_query($connexion,$req);

$req="update PRODUIT set QUANTITE_PRODUIT = $stock - $quantite where ID_PRODUIT = $id_produit ";
mysqli_query($connexion,$req) or die(mysqli_error());


echo 1;
  
mysqli_close($connexion);
?>