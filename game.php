<?php
session_start();

// Kontrola, zda je uživatel přihlášen
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$userName = $_SESSION['user_name'];
$isAnonymous = isset($_SESSION['is_anonymous']) && $_SESSION['is_anonymous'];
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOJOVKA - Hra</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .game-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .user-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
        }
        
        .user-badge {
            background: #f7931e;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background: #c82333;
        }
        
        .game-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .welcome-message {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }
        
        .game-info {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .map-placeholder {
            width: 100%;
            height: 400px;
            background: #f0f0f0;
            border-radius: 8px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="game-container">
        <div class="header">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo $isAnonymous ? '🦊' : '👤'; ?>
                </div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                    <?php if ($isAnonymous): ?>
                        <span class="user-badge">ANONYMNÍ</span>
                    <?php endif; ?>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">Odhlásit se</a>
        </div>
        
        <div class="game-content">
            <h1 class="welcome-message">🎮 Vítej v BOJOVCE, <?php echo htmlspecialchars($userName); ?>!</h1>
            
            <div class="game-info">
                <p>Tvé dobrodružství začíná zde!</p>
                <p>Brzy zde bude mapa, úkoly a další herní prvky.</p>
            </div>
            
            <div class="map-placeholder">
                📍 Zde bude mapa s tvou aktuální pozicí
            </div>
            
            <p class="game-info">
                <strong>Status:</strong> Připraven k akci! 🚀
            </p>
        </div>
    </div>
</body>
</html>