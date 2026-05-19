<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page introuvable - STORESUITE</title>
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
        
        .search-box {
            margin: 30px 0;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 15px 20px;
            font-size: 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .search-box input:focus {
            border-color: #667eea;
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
        
        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }
        
        .quick-link {
            padding: 15px;
            background: #f7fafc;
            border-radius: 10px;
            text-decoration: none;
            color: #4a5568;
            transition: all 0.3s;
        }
        
        .quick-link:hover {
            background: #edf2f7;
            transform: translateY(-2px);
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
            
            .links-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="icon">🔍</div>
        <div class="error-code">404</div>
        <h1 class="error-title">Page Introuvable</h1>
        <p class="error-message">
            Oups! La page que vous recherchez n'existe pas ou a été déplacée.
        </p>
        
        <div class="links-grid">
            <a href="/login.php" class="quick-link">🔐 Connexion</a>
            <a href="/accueil.php" class="quick-link">🏠 Accueil</a>
            <a href="/vente_professionnel.php" class="quick-link">💰 Ventes</a>
            <a href="/listes.php" class="quick-link">📊 Produits</a>
        </div>
        
        <div class="error-actions">
            <a href="/" class="btn btn-primary">🏠 Page d'accueil</a>
            <a href="javascript:history.back()" class="btn btn-secondary">← Retour</a>
        </div>
    </div>
</body>
</html>
