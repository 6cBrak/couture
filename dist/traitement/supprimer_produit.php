<?php
    session_start();
	
    require_once("connexion.php");
	
	$id_produit = htmlspecialchars(trim(addslashes($_GET['id_produit'])));
        
    $req="delete from PRODUIT where ID_PRODUIT = $id_produit ";
    $rs=mysqli_query($connexion,$req) or die(mysqli_error());
	
    echo 1;
  
    mysqli_close($connexion);
	
?>