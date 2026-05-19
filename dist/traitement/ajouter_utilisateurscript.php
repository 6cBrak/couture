<?php
session_start();
require_once("connexion.php");

$login = htmlspecialchars(trim(addslashes($_POST['login'])));
$nom = htmlspecialchars(trim(addslashes($_POST['nom'])));
$passe = htmlspecialchars(trim(addslashes($_POST['passe'])));
$niveau = htmlspecialchars(trim(addslashes($_POST['niveau'])));

$passe = md5($passe);

$req = "select * from UTILISATEUR where LOGIN = '$login' and PASSE = '$passe' ";
$rs = mysqli_query($connexion, $req);

if ($u=mysqli_fetch_assoc($rs))
{
   echo 2;
}
else
{            
   $req="insert into UTILISATEUR(LOGIN,PASSE,NOM,NIVEAU) values ('$login','$passe','$nom',$niveau)";
   mysqli_query($connexion,$req);


   echo 1;
}
  
mysqli_close($connexion);
?>