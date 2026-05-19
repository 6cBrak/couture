<?php
    session_start();
	
    require_once("connexion.php");
	
	$id_utilisateur = htmlspecialchars(trim(addslashes($_GET['id_utilisateur'])));
        
    $req="delete from UTILISATEUR where ID_UTILISATEUR = $id_utilisateur ";
    $rs=mysqli_query($connexion,$req) or die(mysqli_error());
	
    header("location:listes.php?code=1&page=2");
  
    mysqli_close($connexion);
	
?>