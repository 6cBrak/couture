<?php
    session_start();
    require_once("connexion.php");
	
	$req="update FAC set FAC = FAC + 1";
    mysqli_query($connexion,$req) or die(mysqli_error());
?>