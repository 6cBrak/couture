<?php 
	session_start();
	require_once("connexion.php");
	
	$login = htmlspecialchars(trim(addslashes($_POST['login'])));
    $passe = htmlspecialchars(trim(addslashes($_POST['passe'])));
	
	$PC = md5($passe);
	
	$req = "select * from UTILISATEUR where LOGIN = '$login' and PASSE = '$PC'";
	$rs = mysqli_query($connexion, $req);
	
	if ($u=mysqli_fetch_assoc($rs))
	{
		$_SESSION['LOGIN'] = $u['LOGIN'];
		$_SESSION['ID'] = $u['ID_UTILISATEUR'];
		$_SESSION['NIVEAU'] = $u['NIVEAU'];

		echo 1;
		
	}
	else
	{            
        echo 0;
	}
?>