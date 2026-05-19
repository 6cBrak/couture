<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur 500 - STORESUITE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .error-container {
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        
        .error-code {
            font-size: 120px;
            font-weight: 900;
            color: #667eea;
            line-height: 1;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 32px;
            color: #2d3748;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .error-message {
            font-size: 18px;
            color: #718096;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .error-details {
            background: #f7fafc;
            border-left: 4px solid #fc8181;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
            border-radius: 5px;
        }
        
        .error-details h3 {
            color: #c53030;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .error-details p {
            color: #4a5568;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 14px 30px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #edf2f7;
            color: #4a5568;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 600px) {
            .error-code {
                font-size: 80px;
            }
            
            .error-title {
                font-size: 24px;
            }
            
            .error-message {
                font-size: 16px;
            }
            
            .error-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="icon">⚠️</div>
        <div class="error-code">500</div>
        <h1 class="error-title">Erreur Interne du Serveur</h1>
        <p class="error-message">
            Désolé, le serveur a rencontré une erreur inattendue. 
            Notre équipe technique a été notifiée et travaille à résoudre le problème.
        </p>
        
        <div class="error-details">
            <h3>Que s'est-il passé ?</h3>
            <p>
                Une erreur interne s'est produite lors du traitement de votre requête. 
                Cela peut être dû à une configuration incorrecte, un problème de base de données 
                ou une erreur de code PHP.
            </p>
        </div>
        
        <div class="error-details">
            <h3>Que faire maintenant ?</h3>
            <p>
                • Attendez quelques minutes et réessayez<br>
                • Vérifiez votre connexion internet<br>
                • Contactez l'administrateur si le problème persiste<br>
                • Essayez de revenir à la page d'accueil
            </p>
        </div>
        
        <div class="error-actions">
            <a href="/" class="btn btn-primary">🏠 Page d'accueil</a>
            <a href="javascript:history.back()" class="btn btn-secondary">← Retour</a>
        </div>
    </div>
</body>
</html>
