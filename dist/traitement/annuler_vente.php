<?php
session_start();
require_once("connexion.php");

$id_produit = htmlspecialchars(trim(addslashes($_GET['id_produit'])));
$id_vente = htmlspecialchars(trim(addslashes($_GET['id_vente'])));
$stock = htmlspecialchars(trim(addslashes($_GET['stock'])));
$quantite = htmlspecialchars(trim(addslashes($_GET['quantite'])));
$prix_vente = htmlspecialchars(trim(addslashes($_GET['prix_vente'])));

$req="update PRODUIT set QUANTITE_PRODUIT = $stock + $quantite where ID_PRODUIT = $id_produit";
mysqli_query($connexion,$req) or die(mysqli_error());
        
$req="delete from VENTE where ID_VENTE = $id_vente ";
$rs=mysqli_query($connexion,$req) or die(mysqli_error());

$req="insert into VENTE_ANNULEE(QUANTITE_VENTE_ANNULEE,PRIX_VENTE_ANNULEE,DATE_VENTE_ANNULEE,ID_PRODUIT) values ($quantite,$prix_vente,NOW(),$id_produit)";
mysqli_query($connexion,$req);


echo 1;
  
mysqli_close($connexion);
?>