<?php
session_start();

// Kontrola přihlášení
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$userName = $_SESSION['user_name'];
$isAnonymous = isset($_SESSION['is_anonymous']) && $_SESSION['is_anonymous'];

// Načti API klíč z config
require_once 'config.php';
$apiKey = MAPY_CZ_API_KEY;
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOJOVKA - Vytvořit hru</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="create.css">
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
        <a href="dashboard.php" class="nav-tab">
            <i class="fas fa-map"></i> Procházet
        </a>
        <a href="create.php" class="nav-tab active">
            <i class="fas fa-plus-circle"></i> Vytvořit
        </a>
        <a href="profile.php" class="nav-tab">
            <i class="fas fa-user"></i> Profil
        </a>
    </div>

    <!-- Content -->
    <div class="content">
        <div class="create-container">
            <div class="page-header">
                <div class="page-header-icon">
                    <i class="fas fa-wand-magic-sparkles"></i>
                </div>
                <div>
                    <h1>Vytvořit novou hru</h1>
                    <p>Navrhněte vlastní dobrodružství a sdílejte ho s ostatními hráči</p>
                </div>
            </div>

            <form id="createGameForm" method="POST" action="savegame.php">
                <!-- Základní informace -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="fas fa-info-circle"></i>
                        Základní informace
                    </h2>

                    <div class="form-group">
                        <label class="form-label">
                            Název hry<span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="game_name" 
                            id="gameName"
                            class="form-input" 
                            placeholder="např. Tajemství Pražského hradu"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Popis<span class="required">*</span>
                        </label>
                        <textarea 
                            name="game_description" 
                            id="gameDescription"
                            class="form-textarea" 
                            placeholder="Popište příběh a cíl vaší hry..."
                            required
                        ></textarea>
                        <div class="form-helper">Napište zajímavý popis, který přiláká další hráče</div>
                    </div>
                </div>

                <!-- Obtížnost -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="fas fa-gauge-high"></i>
                        Obtížnost
                    </h2>

                    <div class="difficulty-options">
                        <label class="difficulty-option">
                            <input type="radio" name="difficulty" value="easy" required>
                            <div class="difficulty-icon">🟢</div>
                            <div class="difficulty-name">Snadná</div>
                            <div class="difficulty-desc">Pro začátečníky</div>
                        </label>

                        <label class="difficulty-option">
                            <input type="radio" name="difficulty" value="medium" required checked>
                            <div class="difficulty-icon">🟡</div>
                            <div class="difficulty-name">Střední</div>
                            <div class="difficulty-desc">Vyváženná výzva</div>
                        </label>

                        <label class="difficulty-option">
                            <input type="radio" name="difficulty" value="hard" required>
                            <div class="difficulty-icon">🔴</div>
                            <div class="difficulty-name">Těžká</div>
                            <div class="difficulty-desc">Pro experty</div>
                        </label>
                    </div>
                </div>

                <!-- Mapa a waypoints -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="fas fa-map-marker-alt"></i>
                        Přidej úkoly na mapu
                    </h2>

                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        Klikni na mapu pro přidání nového úkolu (waypoint)
                    </div>

                    <!-- Mapa -->
                    <div class="map-container">
                        <div class="map-header">
                            <h3><i class="fas fa-map"></i> Umísti úkoly</h3>
                            <div class="map-controls">
                                <button type="button" id="myLocationBtn" class="btn-icon" title="Moje poloha">
                                    <i class="fas fa-location-arrow"></i>
                                </button>
                                <button type="button" id="clearMarkersBtn" class="btn-icon" title="Smazat všechny značky">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div id="map" data-apikey="<?php echo htmlspecialchars($apiKey); ?>"></div>
                        <div class="map-legend">
                            <div class="legend-item">
                                <span class="legend-marker" style="background: #f7931e;">1</span>
                                <span>Klikni na mapu pro přidání úkolu</span>
                            </div>
                        </div>
                    </div>

                    <!-- Route Statistics -->
                    <div id="routeStats"></div>

                    <!-- Seznam úkolů -->
                    <div id="tasksList" class="tasks-list">
                        <p class="no-tasks">Zatím nejsou přidány žádné úkoly</p>
                    </div>

                    <!-- Hidden input pro waypoints -->
                    <input type="hidden" id="waypointsData" name="waypoints">
                </div>

                <!-- Další detaily -->
                <div class="form-section">
                    <h2 class="form-section-title">
                        <i class="fas fa-clock"></i>
                        Další detaily
                    </h2>

                    <div class="form-group">
                        <label class="form-label">
                            Odhadovaný čas dokončení
                        </label>
                        <select name="estimated_time" class="form-select">
                            <option value="30">30 minut</option>
                            <option value="60" selected>1 hodina</option>
                            <option value="90">1.5 hodiny</option>
                            <option value="120">2 hodiny</option>
                            <option value="180">3+ hodiny</option>
                        </select>
                    </div>
                </div>

                <!-- Akční tlačítka -->
                <div class="form-actions">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Zrušit
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Vytvořit hru
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Custom JS -->
    <script src="create.js"></script>
</body>
</html>