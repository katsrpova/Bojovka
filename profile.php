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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #F5F5F0;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #8B4513 0%, #D2691E 100%);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .logo-text h1 {
            color: white;
            font-size: 24px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .logo-text p {
            color: rgba(255,255,255,0.9);
            font-size: 12px;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar-small {
            width: 35px;
            height: 35px;
            background: #FF69B4;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .user-name {
            color: white;
            font-size: 14px;
            font-weight: 500;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Navigation */
        .nav-tabs {
            background: white;
            display: flex;
            justify-content: space-around;
            padding: 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .nav-tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            border: none;
            background: white;
            cursor: pointer;
            font-size: 14px;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .nav-tab:hover {
            background: #f9f9f9;
        }

        .nav-tab.active {
            color: #8B4513;
            border-bottom-color: #8B4513;
            font-weight: 600;
        }

        /* Profile Content */
        .content {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Profile Header */
        .profile-header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .profile-main {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: #8B4513;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            font-weight: bold;
        }

        .profile-info h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }

        .profile-stats {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #8B4513;
            font-size: 16px;
        }

        .stat-icon {
            font-size: 18px;
        }

        .stat-value {
            font-weight: 600;
        }

        /* Tab Content */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Friends List */
        .friends-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
        }

        .friend-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s;
            cursor: pointer;
        }

        .friend-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .friend-avatar {
            width: 50px;
            height: 50px;
            background: #8B4513;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            font-weight: bold;
            position: relative;
        }

        .online-indicator {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 12px;
            height: 12px;
            background: #10B981;
            border: 2px solid white;
            border-radius: 50%;
        }

        .offline-indicator {
            background: #999;
        }

        .friend-info {
            flex: 1;
        }

        .friend-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .friend-stats {
            font-size: 13px;
            color: #666;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #666;
        }

        .empty-state p {
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .logo-text h1 { font-size: 20px; }
            .logo-text p { display: none; }
            .user-name { display: none; }
            
            .profile-main {
                flex-direction: column;
                text-align: center;
            }

            .profile-stats {
                justify-content: center;
            }

            .friends-list {
                grid-template-columns: 1fr;
            }

            .nav-tab {
                flex-direction: column;
                gap: 4px;
                font-size: 12px;
            }
        }
    </style>
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