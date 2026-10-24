<?php
session_start();

// Kontrola přihlášení
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$userName = $_SESSION['user_name'];
$isAnonymous = isset($_SESSION['is_anonymous']) && $_SESSION['is_anonymous'];

require_once 'config.php';
$pdo = getDB();
$userId = $_SESSION['user_id'];

$pointsStmt = $pdo->prepare("SELECT points FROM users WHERE id = :id");
$pointsStmt->execute(['id' => $userId]);
$points = (int)($pointsStmt->fetchColumn() ?: 0);

$completedStmt = $pdo->prepare("SELECT COUNT(*) FROM game_progress WHERE user_id = :id AND completed = 1");
$completedStmt->execute(['id' => $userId]);
$completedCount = (int)$completedStmt->fetchColumn();

$createdStmt = $pdo->prepare(
    "SELECT g.id, g.title, g.play_count, COUNT(w.id) AS waypoint_count
     FROM games g LEFT JOIN waypoints w ON w.game_id = g.id
     WHERE g.creator_id = :id GROUP BY g.id ORDER BY g.created_at DESC"
);
$createdStmt->execute(['id' => $userId]);
$createdGames = $createdStmt->fetchAll();

$playedStmt = $pdo->prepare(
    "SELECT g.id, g.title, gp.waypoints_completed, gp.completed,
            (SELECT COUNT(*) FROM waypoints w WHERE w.game_id = g.id) AS waypoint_count
     FROM game_progress gp JOIN games g ON g.id = gp.game_id
     WHERE gp.user_id = :id ORDER BY gp.started_at DESC"
);
$playedStmt->execute(['id' => $userId]);
$playedGames = $playedStmt->fetchAll();

$friendsStmt = $pdo->prepare(
    "SELECT u.id, u.name,
            (SELECT COUNT(*) FROM games g WHERE g.creator_id = u.id) AS created_count,
            (SELECT COUNT(*) FROM game_progress gp WHERE gp.user_id = u.id AND gp.completed = 1) AS played_count
     FROM friendships f JOIN users u ON u.id = f.friend_id
     WHERE f.user_id = :id ORDER BY u.name"
);
$friendsStmt->execute(['id' => $userId]);
$myFriends = $friendsStmt->fetchAll();

$discoverStmt = $pdo->prepare(
    "SELECT u.id, u.name,
            (SELECT COUNT(*) FROM games g WHERE g.creator_id = u.id) AS created_count,
            (SELECT COUNT(*) FROM game_progress gp WHERE gp.user_id = u.id AND gp.completed = 1) AS played_count
     FROM users u
     WHERE u.id != :id
       AND u.id NOT IN (SELECT friend_id FROM friendships WHERE user_id = :id2)
     ORDER BY u.created_at DESC"
);
$discoverStmt->execute(['id' => $userId, 'id2' => $userId]);
$discoverUsers = $discoverStmt->fetchAll();

$userStats = [
    'points' => $points,
    'completed' => $completedCount,
    'created' => count($createdGames),
    'friends' => count($myFriends),
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

        .section-title {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
            margin-top: 10px;
        }

        .friends-section {
            margin-bottom: 30px;
        }

        .btn-add-friend {
            background: #8B4513;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-add-friend:hover {
            background: #6b3410;
        }

        .btn-remove-friend {
            background: none;
            border: none;
            color: #c94040;
            cursor: pointer;
            font-size: 18px;
            padding: 6px;
        }

        .friend-form {
            margin: 0;
        }

        .game-card-link {
            text-decoration: none;
            color: inherit;
            display: flex;
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
                <div class="profile-avatar"><?php echo $isAnonymous ? '🦊' : htmlspecialchars(mb_strtoupper(mb_substr($userName, 0, 1))); ?></div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($userName); ?></h2>
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

        <!-- Sub-tab switcher -->
        <div class="nav-tabs" style="border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
            <button class="nav-tab profile-subtab-btn active" data-tab="friendsTab" onclick="switchTab('friendsTab')">
                <i class="fas fa-user-friends"></i> Přátelé
            </button>
            <button class="nav-tab profile-subtab-btn" data-tab="createdTab" onclick="switchTab('createdTab')">
                <i class="fas fa-map-marked-alt"></i> Vytvořené hry
            </button>
            <button class="nav-tab profile-subtab-btn" data-tab="playedTab" onclick="switchTab('playedTab')">
                <i class="fas fa-trophy"></i> Odehrané hry
            </button>
        </div>

        <!-- Tabs Content -->
        <div class="tab-content active" id="friendsTab">
            <div class="friends-section">
                <h3 class="section-title">Moji přátelé</h3>
                <?php if (empty($myFriends)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-friends"></i>
                    <h3>Zatím nemáte žádné přátele</h3>
                    <p>Přidejte si někoho ze seznamu uživatelů níže</p>
                </div>
                <?php else: ?>
                <div class="friends-list">
                    <?php foreach ($myFriends as $friend): ?>
                    <div class="friend-card">
                        <div class="friend-avatar"><?php echo htmlspecialchars(mb_strtoupper(mb_substr($friend['name'], 0, 1))); ?></div>
                        <div class="friend-info">
                            <div class="friend-name"><?php echo htmlspecialchars($friend['name']); ?></div>
                            <div class="friend-stats">
                                <?php echo (int)$friend['created_count']; ?> vytvořeno • <?php echo (int)$friend['played_count']; ?> odehráno
                            </div>
                        </div>
                        <form method="POST" action="friends.php" class="friend-form">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($friend['id']); ?>">
                            <button type="submit" class="btn-remove-friend" title="Odebrat z přátel">
                                <i class="fas fa-user-minus"></i>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="friends-section">
                <h3 class="section-title">Objevit další uživatele</h3>
                <?php if (empty($discoverUsers)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>Žádní další uživatelé k objevení</h3>
                </div>
                <?php else: ?>
                <div class="friends-list">
                    <?php foreach ($discoverUsers as $user): ?>
                    <div class="friend-card" style="cursor: default;">
                        <div class="friend-avatar"><?php echo htmlspecialchars(mb_strtoupper(mb_substr($user['name'], 0, 1))); ?></div>
                        <div class="friend-info">
                            <div class="friend-name"><?php echo htmlspecialchars($user['name']); ?></div>
                            <div class="friend-stats">
                                <?php echo (int)$user['created_count']; ?> vytvořeno • <?php echo (int)$user['played_count']; ?> odehráno
                            </div>
                        </div>
                        <form method="POST" action="friends.php" class="friend-form">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['id']); ?>">
                            <button type="submit" class="btn-add-friend">
                                <i class="fas fa-user-plus"></i> Přidat
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" id="createdTab">
            <?php if (empty($createdGames)): ?>
            <div class="empty-state">
                <i class="fas fa-map-marked-alt"></i>
                <h3>Zatím jste nevytvořili žádné hry</h3>
                <p>Klikněte na "Vytvořit" a začněte tvořit vlastní dobrodružství!</p>
            </div>
            <?php else: ?>
            <div class="friends-list">
                <?php foreach ($createdGames as $game): ?>
                <a href="qr_share.php?id=<?php echo urlencode($game['id']); ?>" class="friend-card game-card-link">
                    <div class="friend-avatar"><i class="fas fa-map"></i></div>
                    <div class="friend-info">
                        <div class="friend-name"><?php echo htmlspecialchars($game['title']); ?></div>
                        <div class="friend-stats">
                            <?php echo (int)$game['waypoint_count']; ?> úkolů • <?php echo (int)$game['play_count']; ?> odehráno
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="tab-content" id="playedTab">
            <?php if (empty($playedGames)): ?>
            <div class="empty-state">
                <i class="fas fa-trophy"></i>
                <h3>Historie her</h3>
                <p>Zde se zobrazí hry, které jste dokončili</p>
            </div>
            <?php else: ?>
            <div class="friends-list">
                <?php foreach ($playedGames as $pg): ?>
                <a href="play.php?id=<?php echo urlencode($pg['id']); ?>" class="friend-card game-card-link">
                    <div class="friend-avatar"><i class="fas <?php echo $pg['completed'] ? 'fa-check-circle' : 'fa-play-circle'; ?>"></i></div>
                    <div class="friend-info">
                        <div class="friend-name"><?php echo htmlspecialchars($pg['title']); ?></div>
                        <div class="friend-stats">
                            <?php echo (int)$pg['waypoints_completed']; ?>/<?php echo (int)$pg['waypoint_count']; ?> úkolů
                            <?php echo $pg['completed'] ? ' • dokončeno' : ' • rozehráno'; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Tab switching
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById(tabName).classList.add('active');

            document.querySelectorAll('.profile-subtab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`.profile-subtab-btn[data-tab="${tabName}"]`).classList.add('active');
        }
    </script>
</body>
</html>