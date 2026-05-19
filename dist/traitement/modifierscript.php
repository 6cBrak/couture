<?php
session_start();
require_once("connexion.php");

if(isset($_GET['id_utilisateur']))
{
    $id_utilisateur = htmlspecialchars(trim(addslashes($_GET['id_utilisateur'])));
    $login = htmlspecialchars(trim(addslashes($_POST['login'])));
    $nom = htmlspecialchars(trim(addslashes($_POST['nom'])));
    $passe = htmlspecialchars(trim(addslashes($_POST['passe'])));
    $passe1 = htmlspecialchars(trim(addslashes($_POST['passe1'])));

    if(isset($_POST['passe']) and $_POST['passe'] == '')
    { 
        $passe = $passe1;
    }
    else 
    {
    
        $passe = md5($passe);
    }

    $req="update UTILISATEUR set LOGIN = '$login' ,PASSE = '$passe' ,NOM = '$nom' where ID_UTILISATEUR = $id_utilisateur";
    mysqli_query($connexion,$req) or die(mysqli_error());
    
    
    echo 1;

}


  
mysqli_close($connexion);
?>