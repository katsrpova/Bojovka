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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="profile.css?v=<?php echo @filemtime(__DIR__ . '/profile.css'); ?>">
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
            <button class="btn-feedback" onclick="openFeedbackModal()">
                <i class="fas fa-comment-dots"></i>
                <span>Zpětná vazba</span>
            </button>
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

    <!-- Feedback Modal -->
    <div class="modal" id="feedbackModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-comment-dots"></i> Zpětná vazba</h3>
                <button class="btn-close" onclick="closeFeedbackModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Název</label>
                    <input type="text" class="form-input" id="feedbackTitle" placeholder="Stručný název..." maxlength="150">
                </div>
                <div class="form-group">
                    <label class="form-label">Zpráva</label>
                    <textarea class="form-textarea" id="feedbackMessage" placeholder="Co se ti líbí, co chybí, co nefunguje..." maxlength="5000"></textarea>
                </div>
                <button class="btn btn-primary" onclick="submitFeedback()" id="feedbackSubmitBtn">
                    <i class="fas fa-paper-plane"></i> Odeslat
                </button>
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

            document.querySelectorAll('.profile-subtab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`.profile-subtab-btn[data-tab="${tabName}"]`).classList.add('active');
        }

        // Zpětná vazba
        function openFeedbackModal() {
            document.getElementById('feedbackModal').classList.add('active');
        }

        function closeFeedbackModal() {
            document.getElementById('feedbackModal').classList.remove('active');
            document.getElementById('feedbackTitle').value = '';
            document.getElementById('feedbackMessage').value = '';
        }

        async function submitFeedback() {
            const title = document.getElementById('feedbackTitle').value.trim();
            const message = document.getElementById('feedbackMessage').value.trim();

            if (!title || !message) {
                alert('Vyplňte prosím název i zprávu.');
                return;
            }

            const btn = document.getElementById('feedbackSubmitBtn');
            btn.disabled = true;

            try {
                const res = await fetch('feedback.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `title=${encodeURIComponent(title)}&message=${encodeURIComponent(message)}`,
                });
                const data = await res.json();

                if (!data.success) throw new Error(data.error || 'Odeslání se nezdařilo');

                alert('Díky za zpětnou vazbu!');
                closeFeedbackModal();
            } catch (err) {
                alert('Nepodařilo se odeslat: ' + err.message);
            } finally {
                btn.disabled = false;
            }
        }

        document.addEventListener('click', function(event) {
            const feedbackModal = document.getElementById('feedbackModal');
            if (event.target === feedbackModal) {
                closeFeedbackModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const feedbackModal = document.getElementById('feedbackModal');
                if (feedbackModal.classList.contains('active')) {
                    closeFeedbackModal();
                }
            }
        });
    </script>
</body>
</html>