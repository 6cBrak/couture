<?php
session_start();
require_once("connexion.php");

$id_vente = htmlspecialchars(trim(addslashes($_GET['id_vente'])));
        
$req="delete from VENTE where ID_VENTE = $id_vente ";
$rs=mysqli_query($connexion,$req) or die(mysqli_error());


echo 1;
  
mysqli_close($connexion);
?>