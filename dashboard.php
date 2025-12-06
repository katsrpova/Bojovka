<?php
session_start();

// Kontrola přihlášení
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$userName = $_SESSION['user_name'];
$isAnonymous = isset($_SESSION['is_anonymous']) && $_SESSION['is_anonymous'];

// Mock data her (později z databáze)
$adventures = [
    [
        'id' => 1,
        'title' => 'Prague Castle Adventure',
        'description' => 'Explore the historic Prague Castle area and discover hidden secrets',
        'distance' => '5.2 km',
        'duration' => '120 min',
        'rating' => 4.8,
        'plays' => 342,
        'difficulty' => 'medium',
        'author' => 'CzechExplorer'
    ],
    [
        'id' => 2,
        'title' => 'Vltava River Trail',
        'description' => 'Follow the beautiful Vltava river through Prague',
        'distance' => '8.5 km',
        'duration' => '180 min',
        'rating' => 4.5,
        'plays' => 218,
        'difficulty' => 'easy',
        'author' => 'RiverRunner'
    ],
    [
        'id' => 3,
        'title' => 'Old Town Mystery',
        'description' => 'Solve puzzles hidden in Prague\'s Old Town',
        'distance' => '3.8 km',
        'duration' => '150 min',
        'rating' => 4.9,
        'plays' => 156,
        'difficulty' => 'hard',
        'author' => 'MysteryMaker'
    ]
];
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOJOVKA - Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Top Header -->
    <header class="top-header">
        <div class="header-content">
            <div class="logo-section">
                <div class="logo">🧭</div>
                <div class="logo-text">
                    <h1>BOJOVKA</h1>
                    <p>Location-Based Adventure Game</p>
                </div>
            </div>
            <div class="user-section">
                <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                <div class="user-avatar">
                    <?php echo $isAnonymous ? '🦊' : '👤'; ?>
                </div>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Odhlásit</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Top Navigation Tabs -->
    <nav class="top-nav">
        <a href="dashboard.php" class="nav-tab active">
            <i class="fas fa-map"></i>
            <span>Procházet</span>
        </a>
        <a href="create.php" class="nav-tab">
            <i class="fas fa-plus-circle"></i>
            <span>Vytvořit</span>
        </a>
        <a href="profile.php" class="nav-tab">
            <i class="fas fa-user"></i>
            <span>Profil</span>
        </a>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-wrapper">
            <!-- Page Header -->
            <div class="page-header">
                <div class="header-left">
                    <h2>Discover Adventures</h2>
                    <p class="subtitle">Choose a quest created by the community</p>
                </div>
                <button class="btn-join-code" onclick="openJoinModal()">
                    <i class="fas fa-qrcode"></i>
                    <span>Join by Code</span>
                </button>
            </div>

            <!-- Search Bar -->
            <div class="search-container">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search by game name or author..." onkeyup="searchAdventures()">
                </div>
            </div>

            <!-- Adventures Grid -->
            <div class="adventures-grid" id="adventuresGrid">
                <?php foreach ($adventures as $adventure): ?>
                <article class="adventure-card">
                    <div class="card-header">
                        <h3><?php echo htmlspecialchars($adventure['title']); ?></h3>
                        <span class="difficulty-badge <?php echo $adventure['difficulty']; ?>">
                            <?php echo $adventure['difficulty']; ?>
                        </span>
                    </div>
                    
                    <p class="description"><?php echo htmlspecialchars($adventure['description']); ?></p>
                    
                    <div class="adventure-stats">
                        <div class="stat">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo $adventure['distance']; ?></span>
                        </div>
                        <div class="stat">
                            <i class="fas fa-clock"></i>
                            <span><?php echo $adventure['duration']; ?></span>
                        </div>
                        <div class="stat">
                            <i class="fas fa-star"></i>
                            <span><?php echo $adventure['rating']; ?>/5</span>
                        </div>
                        <div class="stat">
                            <i class="fas fa-users"></i>
                            <span><?php echo $adventure['plays']; ?> plays</span>
                        </div>
                    </div>

                    <div class="card-footer">
                        <span class="author">by <?php echo htmlspecialchars($adventure['author']); ?></span>
                        <button class="btn-start" onclick="startAdventure(<?php echo $adventure['id']; ?>)">
                            Start Adventure
                        </button>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script src="dashboard.js"></script>
</body>
</html>