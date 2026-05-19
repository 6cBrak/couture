<?php
/**
 * ============================================================================
 * PAGE DE SUCCÈS APRÈS CONFIGURATION INITIALE
 * ============================================================================
 * 
 * Importance : Confirmation de la configuration réussie du système
 * 
 * ============================================================================
 */

session_start();

// Vérifier qu'on vient bien de terminer la configuration
if (!isset($_SESSION['setup_success'])) {
    header('Location: index.php');
    exit;
}

unset($_SESSION['setup_success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration réussie - STORESUITE</title>
    <link href="./assets/css/tabler.min.css" rel="stylesheet"/>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .success-container {
            max-width: 600px;
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .success-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(56, 239, 125, 0.4);
            animation: scaleIn 0.6s ease-out 0.2s both;
        }
        
        @keyframes scaleIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .checkmark {
            width: 60px;
            height: 60px;
            stroke: white;
            stroke-width: 3;
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: drawCheck 0.8s ease-out 0.5s forwards;
        }
        
        @keyframes drawCheck {
            to {
                stroke-dashoffset: 0;
            }
        }
        
        h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 15px;
            animation: fadeIn 0.6s ease-out 0.3s both;
        }
        
        .success-message {
            font-size: 1.1rem;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 35px;
            animation: fadeIn 0.6s ease-out 0.4s both;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .btn-launch {
            display: inline-block;
            padding: 16px 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            animation: fadeIn 0.6s ease-out 0.5s both;
        }
        
        .btn-launch:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.5);
            color: white;
        }
        
        .btn-launch svg {
            vertical-align: middle;
            margin-right: 8px;
        }
        
        .countdown-timer {
            margin-top: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 15px;
            animation: fadeIn 0.6s ease-out 0.6s both;
        }
        
        .countdown-number {
            display: inline-block;
            width: 50px;
            height: 50px;
            line-height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 10px;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .features {
            display: flex;
            justify-content: space-around;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
            animation: fadeIn 0.6s ease-out 0.7s both;
        }
        
        .feature-item {
            text-align: center;
            flex: 1;
            padding: 0 15px;
        }
        
        .feature-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        
        .feature-icon svg {
            width: 24px;
            height: 24px;
            stroke: white;
        }
        
        .feature-title {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.9rem;
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #f0f;
            position: absolute;
            animation: confetti-fall 3s linear infinite;
        }
        
        @keyframes confetti-fall {
            to {
                transform: translateY(100vh) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <path fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
        </div>
        
        <h1>Configuration réussie !</h1>
        
        <p class="success-message">
            Félicitations ! Votre système <strong>STORE SUITE</strong> est maintenant prêt à l'emploi.<br>
            Vous pouvez commencer à gérer votre stock et vos ventes.
        </p>
        
        <a href="login.php" class="btn-launch">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                <polyline points="10 17 15 12 10 7"/>
                <line x1="15" y1="12" x2="3" y2="12"/>
            </svg>
            Accéder à la connexion
        </a>
        
        <div class="countdown-timer">
            <p style="color: #64748b; margin: 0;">Redirection automatique dans</p>
            <span class="countdown-number" id="seconds">5</span>
            <p style="color: #64748b; margin: 5px 0 0 0; font-size: 0.9rem;">secondes</p>
        </div>
        
        <div class="features">
            <div class="feature-item">
                <div class="feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <line x1="9" y1="3" x2="9" y2="21"/>
                    </svg>
                </div>
                <div class="feature-title">Gestion de stock</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <div class="feature-title">Facturation</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div class="feature-title">Rapports</div>
            </div>
        </div>
    </div>
    
    <script>
        // Effet confetti
        function createConfetti() {
            const colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#f9ca24', '#6c5ce7', '#fd79a8'];
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + '%';
                    confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.animationDelay = Math.random() * 3 + 's';
                    confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => confetti.remove(), 5000);
                }, i * 30);
            }
        }
        
        // Lancer les confettis au chargement
        window.addEventListener('load', createConfetti);
        
        // Compte à rebours de redirection
        let seconds = 5;
        const countdownElement = document.getElementById('seconds');
        
        const countdown = setInterval(function() {
            seconds--;
            countdownElement.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(countdown);
                window.location.href = 'login.php';
            }
        }, 1000);
    </script>
</body>
</html>
