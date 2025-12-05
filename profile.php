<?php
session_start();

// Kontrola přihlášení
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$userName = $_SESSION['user_name'];
$isAnonymous = isset($_SESSION['is_anonymous']) && $_SESSION['is_anonymous'];

// Mock data pro demo (později z databáze)
$userStats = [
    'points' => 625,
    'completed' => 2,
    'created' => 0,
    'friends' => 5
];

$friends = [
    ['name' => 'CzechExplorer', 'initial' => 'C', 'created' => 12, 'played' => 45, 'online' => true],
    ['name' => 'RiverRunner', 'initial' => 'R', 'created' => 8, 'played' => 32, 'online' => true],
    ['name' => 'MysteryMaker', 'initial' => 'M', 'created' => 15, 'played' => 28, 'online' => false],
    ['name' => 'AdventureSeeker', 'initial' => 'A', 'created' => 5, 'played' => 67, 'online' => false],
    ['name' => 'TreasureHunter', 'initial' => 'T', 'created' => 20, 'played' => 89, 'online' => true],
];
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOJOVKA - Profil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo-section">
            <div class="logo-icon">🧭</div>
            <div class="logo-text">
                <h1>BOJOVKA</h1>
                <p>Location-Based Adventure Game</p>
            </div>
        </div>
        <div class="user-section">
            <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
            <div class="user-avatar-small">
                <?php echo $isAnonymous ? '🦊' : '👤'; ?>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Odhlásit
            </a>
        </div>
    </div>

    <!-- Navigation -->
    <div class="nav-tabs">
        <button class="nav-tab" onclick="window.location.href='dashboard.php'">
            <i class="fas fa-map"></i> Procházet
        </button>
        <button class="nav-tab" onclick="window.location.href='create.php'">
            <i class="fas fa-plus-circle"></i> Vytvořit
        </button>
        <button class="nav-tab active">
            <i class="fas fa-user"></i> Profil
        </button>
    </div>

    <!-- Content -->
    <div class="content">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-main">
                <div class="profile-avatar">You</div>
                <div class="profile-info">
                    <h2>Your Profile</h2>
                    <div class="profile-stats">
                        <div class="stat-item">
                            <i class="fas fa-trophy stat-icon"></i>
                            <span><span class="stat-value"><?php echo $userStats['points']; ?></span> points</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-check-circle stat-icon"></i>
                            <span><span class="stat-value"><?php echo $userStats['completed']; ?></span> completed</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-map-marker-alt stat-icon"></i>
                            <span><span class="stat-value"><?php echo $userStats['created']; ?></span> created</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-users stat-icon"></i>
                            <span><span class="stat-value"><?php echo $userStats['friends']; ?></span> friends</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Content -->
        <div class="tab-content active" id="friendsTab">
            <div class="friends-list">
                <?php foreach ($friends as $friend): ?>
                <div class="friend-card">
                    <div class="friend-avatar">
                        <?php echo $friend['initial']; ?>
                        <div class="<?php echo $friend['online'] ? 'online-indicator' : 'online-indicator offline-indicator'; ?>"></div>
                    </div>
                    <div class="friend-info">
                        <div class="friend-name"><?php echo htmlspecialchars($friend['name']); ?></div>
                        <div class="friend-stats">
                            <?php echo $friend['created']; ?> created • <?php echo $friend['played']; ?> played
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="tab-content" id="createdTab">
            <div class="empty-state">
                <i class="fas fa-map-marked-alt"></i>
                <h3>Zatím jste nevytvořili žádné hry</h3>
                <p>Klikněte na "Vytvořit" a začněte tvořit vlastní dobrodružství!</p>
            </div>
        </div>

        <div class="tab-content" id="playedTab">
            <div class="empty-state">
                <i class="fas fa-trophy"></i>
                <h3>Historie her</h3>
                <p>Zde se zobrazí hry, které jste dokončili</p>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById(tabName).classList.add('active');
        }
    </script>
</body>
</html>