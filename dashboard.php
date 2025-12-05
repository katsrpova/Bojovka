<?php
session_start();

// Kontrola přihlášení
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
    <title>BOJOVKA - Dashboard</title>
    <link rel="stylesheet" href="style_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header with user info -->
    <div class="top-header">
        <div class="user-card">
            <div class="user-avatar">
                <?php echo $isAnonymous ? '🦊' : '👤'; ?>
            </div>
            <div class="user-info">
                <h2><?php echo htmlspecialchars($userName); ?></h2>
                <?php if ($isAnonymous): ?>
                    <span class="badge-anonymous">ANONYMNÍ</span>
                <?php endif; ?>
            </div>
            <a href="logout.php" class="btn-logout">Odhlásit se</a>
        </div>
    </div>

    <!-- Main content -->
    <div class="welcome-container">
        <div class="welcome-card">
            <div class="welcome-icon">🎮</div>
            <h1>Vítej v BOJOVCE, <?php echo htmlspecialchars($userName); ?>!</h1>
            <p class="subtitle">Tvé dobrodružství začíná zde!</p>
            <p class="description">Brzy zde bude mapa, úkoly a další herní prvky.</p>

            <div class="map-placeholder">
                <i class="fas fa-map-marked-alt"></i>
                <p>Zde bude mapa s tvou aktuální pozicí</p>
            </div>

            <div class="quick-actions">
                <a href="browse.php" class="btn btn-primary">
                    <i class="fas fa-compass"></i>
                    Procházet hry
                </a>
                <a href="create.php" class="btn btn-secondary">
                    <i class="fas fa-plus-circle"></i>
                    Vytvořit hru
                </a>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="dashboard.php" class="nav-item active">
            <i class="fas fa-home"></i>
            <span>Domů</span>
        </a>
        <a href="browse.php" class="nav-item">
            <i class="fas fa-map"></i>
            <span>Procházet</span>
        </a>
        <a href="create.php" class="nav-item">
            <i class="fas fa-plus-circle"></i>
            <span>Vytvořit</span>
        </a>
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user"></i>
            <span>Profil</span>
        </a>
    </nav>
</body>
</html>