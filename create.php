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
    <title>BOJOVKA - Vytvořit hru</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="create.css">
    
    <!-- MAPY.CZ API - Oficiální způsob -->
    <script type="text/javascript" src="https://api.mapy.cz/loader.js"></script>
    <script type="text/javascript">Loader.lang = "cs"; Loader.load();</script>
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

            <form id="createGameForm" method="POST" action="save_game.php">
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
                        <div id="map"></div>
                        <div class="map-legend">
                            <div class="legend-item">
                                <span class="legend-marker">1</span>
                                <span>Klikni na mapu pro přidání úkolu</span>
                            </div>
                        </div>
                    </div>

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

    <script>
        // Globální proměnné
        let map;
        let markerLayer;
        let waypoints = [];
        let waypointCounter = 0;

        // Počkej na plné načtení stránky
        window.addEventListener('load', function() {
            console.log('Page loaded, waiting for API...');
            
            // Počkej na Loader
            let attempts = 0;
            const checkLoader = setInterval(function() {
                attempts++;
                
                if (typeof Loader !== 'undefined' && typeof SMap !== 'undefined') {
                    console.log('✓ Mapy.cz API loaded!');
                    clearInterval(checkLoader);
                    initMap();
                } else if (attempts > 20) {
                    console.error('❌ Failed to load Mapy.cz API after 20 attempts');
                    clearInterval(checkLoader);
                    document.getElementById('map').innerHTML = '<div style="padding: 40px; text-align: center; color: #dc3545; background: #fff;">Nepodařilo se načíst Mapy.cz API.<br><small>Zkuste vypnout adblocker nebo použít jiný prohlížeč.</small></div>';
                }
            }, 500);
        });

        function initMap() {
            try {
                const mapElement = document.getElementById("map");
                
                if (!mapElement) {
                    console.error('Map element not found!');
                    return;
                }
                
                // Vytvoř střed mapy (Praha)
                const center = SMap.Coords.fromWGS84(14.4378, 50.0755);
                
                // Vytvoř mapu
                map = new SMap(mapElement, center, 13);
                console.log('✓ Map created');
                
                // Přidej základní vrstvu
                map.addDefaultLayer(SMap.DEF_BASE).enable();
                
                // Přidej ovládací prvky
                map.addDefaultControls();
                console.log('✓ Controls added');
                
                // Vytvoř vrstvu pro značky
                markerLayer = new SMap.Layer.Marker();
                map.addLayer(markerLayer);
                markerLayer.enable();
                console.log('✓ Marker layer ready');
                
                // Přidej listener pro kliknutí na mapu
                map.getSignals().addListener(window, "map-click", function(e) {
                    const coords = SMap.Coords.fromEvent(e.data.event, map);
                    addWaypoint(coords);
                });
                console.log('✓ Map is ready! Click to add waypoints.');
                
                // Získej polohu uživatele
                getUserLocation();
            } catch (error) {
                console.error('Error initializing map:', error);
            }
        }

        function getUserLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const userCoords = SMap.Coords.fromWGS84(
                        position.coords.longitude, 
                        position.coords.latitude
                    );
                    map.setCenterZoom(userCoords, 15);
                    console.log('✓ User location:', position.coords.latitude, position.coords.longitude);
                }, function(error) {
                    console.log('Geolocation error:', error);
                });
            }
        }

        function addWaypoint(coords) {
            waypointCounter++;
            
            const waypoint = {
                id: waypointCounter,
                lat: coords.y,
                lng: coords.x,
                name: `Úkol ${waypointCounter}`,
                description: '',
                type: 'checkpoint'
            };
            
            waypoints.push(waypoint);
            
            // Vytvoř marker
            const marker = new SMap.Marker(coords, waypointCounter.toString(), {
                title: waypoint.name
            });
            markerLayer.addMarker(marker);
            
            console.log('✓ Waypoint added:', waypoint.name);
            
            updateTasksList();
            updateWaypointsData();
        }

        function updateTasksList() {
            const tasksList = document.getElementById('tasksList');
            
            if (waypoints.length === 0) {
                tasksList.innerHTML = '<p class="no-tasks">Zatím nejsou přidány žádné úkoly</p>';
                return;
            }
            
            tasksList.innerHTML = waypoints.map(wp => `
                <div class="task-item" data-id="${wp.id}">
                    <div class="task-number">${wp.id}</div>
                    <div class="task-content">
                        <input 
                            type="text" 
                            class="task-input" 
                            value="${wp.name}"
                            placeholder="Název úkolu"
                            onchange="updateWaypointName(${wp.id}, this.value)"
                        >
                        <textarea 
                            class="task-textarea" 
                            placeholder="Popis úkolu nebo otázka..."
                            onchange="updateWaypointDescription(${wp.id}, this.value)"
                        >${wp.description}</textarea>
                    </div>
                    <button type="button" class="btn-delete" onclick="removeWaypoint(${wp.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `).join('');
        }

        function updateWaypointName(id, name) {
            const wp = waypoints.find(w => w.id === id);
            if (wp) {
                wp.name = name;
                updateWaypointsData();
            }
        }

        function updateWaypointDescription(id, description) {
            const wp = waypoints.find(w => w.id === id);
            if (wp) {
                wp.description = description;
                updateWaypointsData();
            }
        }

        function removeWaypoint(id) {
            waypoints = waypoints.filter(w => w.id !== id);
            
            // Znovu vytvoř všechny markery
            markerLayer.removeAll();
            waypoints.forEach(wp => {
                const coords = SMap.Coords.fromWGS84(wp.lng, wp.lat);
                const marker = new SMap.Marker(coords, wp.id.toString(), {
                    title: wp.name
                });
                markerLayer.addMarker(marker);
            });
            
            updateTasksList();
            updateWaypointsData();
        }

        function updateWaypointsData() {
            document.getElementById('waypointsData').value = JSON.stringify(waypoints);
        }

        // Tlačítko Moje poloha
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('myLocationBtn').addEventListener('click', function() {
                getUserLocation();
            });

            // Tlačítko Smazat všechny značky
            document.getElementById('clearMarkersBtn').addEventListener('click', function() {
                if (confirm('Opravdu chceš smazat všechny úkoly?')) {
                    waypoints = [];
                    waypointCounter = 0;
                    
                    if (markerLayer) {
                        markerLayer.removeAll();
                    }
                    
                    updateTasksList();
                    updateWaypointsData();
                }
            });

            // Difficulty selection
            document.querySelectorAll('.difficulty-option').forEach(option => {
                option.addEventListener('click', function() {
                    document.querySelectorAll('.difficulty-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    this.classList.add('selected');
                    this.querySelector('input[type="radio"]').checked = true;
                });
            });

            const selectedRadio = document.querySelector('input[name="difficulty"]:checked');
            if (selectedRadio) {
                selectedRadio.closest('.difficulty-option').classList.add('selected');
            }

            // Form submit
            document.getElementById('createGameForm').addEventListener('submit', function(e) {
                if (waypoints.length === 0) {
                    e.preventDefault();
                    alert('Přidej alespoň jeden úkol na mapu!');
                    return;
                }
                
                if (waypoints.length < 3) {
                    if (!confirm('Doporučujeme přidat alespoň 3 úkoly. Chceš pokračovat?')) {
                        e.preventDefault();
                        return;
                    }
                }
            });
        });
    </script>
</body>
</html>