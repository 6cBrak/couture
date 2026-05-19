<?php
/**
 * Fichier de test simple pour vérifier que le serveur fonctionne
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Serveur - STORESUITE</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .box {
            background: rgba(255,255,255,0.1);
            padding: 30px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        .success {
            color: #4ade80;
            font-size: 48px;
            text-align: center;
            margin-bottom: 20px;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
        }
        .info {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .label {
            font-weight: bold;
            color: #fbbf24;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="success">✓</div>
        <h1>Le serveur fonctionne !</h1>
        
        <div class="info">
            <span class="label">Date et heure :</span> 
            <?php echo date('d/m/Y H:i:s'); ?>
        </div>
        
        <div class="info">
            <span class="label">Version PHP :</span> 
            <?php echo phpversion(); ?>
        </div>
        
        <div class="info">
            <span class="label">Serveur :</span> 
            <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Non disponible'; ?>
        </div>
        
        <div class="info">
            <span class="label">URL actuelle :</span> 
            <?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>
        </div>
        
        <div class="info">
            <span class="label">Chemin du fichier :</span> 
            <?php echo __FILE__; ?>
        </div>
    </div>
</body>
</html>
