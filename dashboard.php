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
    <meta http-equiv="Content-Security-Policy" content="script-src 'self' 'unsafe-inline' https://api.mapy.cz;">
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

    <!-- Join by Code Modal -->
    <div class="modal" id="joinModal">
    <div class="modal-content">
 
        <!-- Header -->
        <div class="modal-header">
            <h3>Připojit se ke hře</h3>
            <button class="btn-close" onclick="closeJoinModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
 
        <!-- Tab switcher -->
        <div class="modal-body">
            <div class="join-tabs">
                <button class="join-tab active" onclick="switchJoinTab('code')" id="tabCode">
                    <i class="fas fa-keyboard"></i> Zadat kód
                </button>
                <button class="join-tab" onclick="switchJoinTab('qr')" id="tabQr">
                    <i class="fas fa-qrcode"></i> Skenovat QR
                </button>
            </div>
 
            <!-- ── CODE TAB ── -->
            <div class="join-content active" id="codeContent">
                <p class="join-description">Zadejte 6-místný kód (písmena + čísla)</p>
 
                <input
                    type="text"
                    class="code-input"
                    id="gameCode"
                    placeholder="ABC123"
                    maxlength="6"
                    autocomplete="off"
                    autocapitalize="characters"
                    spellcheck="false"
                    oninput="onCodeInput(this)"
                >
                <div id="codeValidationMsg" style="min-height:24px;margin-bottom:10px;font-size:13px;text-align:center;"></div>
 
                <button class="btn-join" onclick="joinByCode()" id="joinCodeBtn" disabled>
                    <i class="fas fa-arrow-right"></i> Připojit se
                </button>
            </div>
 
            <!-- ── QR TAB ── -->
            <div class="join-content" id="qrContent">
                <!-- Camera view -->
                <div id="scannerContainer" style="position:relative;border-radius:12px;overflow:hidden;background:#000;aspect-ratio:1/1;margin-bottom:14px;">
                    <video id="qrVideo" style="width:100%;height:100%;object-fit:cover;" playsinline muted></video>
                    <!-- Overlay frame -->
                    <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;">
                        <div style="
                            width:65%;aspect-ratio:1/1;
                            border:3px solid rgba(247,147,30,.9);
                            border-radius:16px;
                            box-shadow:0 0 0 9999px rgba(0,0,0,.45);
                        "></div>
                    </div>
                    <!-- Corner deco -->
                    <svg style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <polyline points="17.5,23.5 17.5,17.5 23.5,17.5" stroke="#f7931e" stroke-width="2" fill="none"/>
                        <polyline points="76.5,17.5 82.5,17.5 82.5,23.5" stroke="#f7931e" stroke-width="2" fill="none"/>
                        <polyline points="17.5,76.5 17.5,82.5 23.5,82.5" stroke="#f7931e" stroke-width="2" fill="none"/>
                        <polyline points="82.5,76.5 82.5,82.5 76.5,82.5" stroke="#f7931e" stroke-width="2" fill="none"/>
                    </svg>
                </div>
 
                <div id="scanStatus" style="text-align:center;font-size:14px;color:#666;margin-bottom:14px;">
                    <i class="fas fa-camera" style="margin-right:6px;"></i>Namiřte kameru na QR kód
                </div>
                <button class="btn-join" id="startScanBtn" onclick="startQrScanner()">
                    <i class="fas fa-camera"></i> Zapnout kameru
                </button>
                <button class="btn-join" id="stopScanBtn" onclick="stopQrScanner()" style="display:none;background:#666;">
                    <i class="fas fa-stop"></i> Zastavit skener
                </button>
            </div>
 
        </div><!-- /.modal-body -->
    </div>
</div>

    <script src="dashboard.js"></script>
</body>
</html>