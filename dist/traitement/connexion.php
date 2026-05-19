<?php
	//include('protection_pages.php');
	//$conn=mysql_connect("91.216.107.161","assal1230252_1tgvai","Gloire2021");
	//mysql_select_db("assal1230252_1tgvai",$conn) or die(mysql_error());
	
	$connexion = mysqli_connect('localhost', 'root', '');
	$db = mysqli_select_db($connexion, 'bd_smart_gestion');

	/*try
	{
	$bdd = new PDO('mysql:host=91.216.107.161;dbname=assal1230252_1tgvai', 'assal1230252_1tgvai', 'Gloire2021');
	}
	catch(Exception $e)
	{
	die('Erreur : '.$e->getMessage());
	}*/

	$req = "select * from PLUS ";
    $rs100 = mysqli_query($connexion,$req);
    $ET100 = mysqli_fetch_assoc($rs100);
	
?>