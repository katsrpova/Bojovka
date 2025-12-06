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
    <title>BOJOVKA - Create Adventure</title>
    <link rel="stylesheet" href="style_create.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- API Mapy.cz -->
    <!-- TODO: Přidat Mapy.cz API script -->
    <!-- <script src="https://api.mapy.cz/loader.js"></script> -->
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

    <!-- Top Navigation -->
    <nav class="top-nav">
        <a href="dashboard.php" class="nav-tab">
            <i class="fas fa-map"></i>
            <span>Procházet</span>
        </a>
        <a href="create.php" class="nav-tab active">
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
        <div class="create-container">
            <!-- Left Sidebar - Game Details -->
            <aside class="sidebar">
                <div class="sidebar-header">
                    <h2>Create New Adventure</h2>
                    <p>Design your quest route with challenges</p>
                </div>

                <div class="form-section">
                    <label>Game Name</label>
                    <input type="text" id="gameName" placeholder="Enter adventure name..." class="form-input">
                </div>

                <div class="form-section">
                    <label>Description</label>
                    <textarea id="gameDescription" placeholder="Describe your adventure..." class="form-textarea"></textarea>
                </div>

                <div class="form-section">
                    <label>Difficulty</label>
                    <select id="gameDifficulty" class="form-select">
                        <option value="easy">Easy</option>
                        <option value="medium">Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                </div>

                <div class="waypoints-section">
                    <div class="section-header">
                        <h3>Waypoints</h3>
                        <span class="waypoint-count" id="waypointCount">0</span>
                    </div>
                    <div id="waypointsList" class="waypoints-list">
                        <p class="empty-message">Click on map to add waypoints</p>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn-save" onclick="saveAdventure()">
                        <i class="fas fa-save"></i>
                        Save Adventure
                    </button>
                    <button class="btn-cancel" onclick="cancelCreate()">
                        Cancel
                    </button>
                </div>
            </aside>

            <!-- Map Container -->
            <div class="map-container">
                <div class="map-toolbar">
                    <button class="tool-btn" title="Add Waypoint" onclick="toggleAddMode()">
                        <i class="fas fa-map-marker-alt"></i>
                    </button>
                    <button class="tool-btn" title="Center Map" onclick="centerMap()">
                        <i class="fas fa-crosshairs"></i>
                    </button>
                    <button class="tool-btn" title="Zoom In" onclick="zoomIn()">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="tool-btn" title="Zoom Out" onclick="zoomOut()">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>

                <!-- API MAPY.CZ - zde bude mapa -->
                <div id="map" class="map">
                    <!-- Mapy.cz se načtou zde -->
                    <div class="map-placeholder">
                        <i class="fas fa-map"></i>
                        <p>Map will load here</p>
                        <small>Mapy.cz API integration</small>
                    </div>
                </div>

                <div class="map-info">
                    <div class="info-item">
                        <i class="fas fa-route"></i>
                        <span>Distance: <strong id="totalDistance">0 km</strong></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <span>Est. Time: <strong id="totalTime">0 min</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="create.js"></script>
</body>
</html>