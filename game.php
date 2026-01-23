<?php
session_start();

// Kontrola, zda je uživatel přihlášen
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
    <meta http-equiv="Content-Security-Policy" content="script-src 'self' 'unsafe-inline' https://api.mapy.cz;">
    <title>BOJOVKA - Hra</title>
    <link rel="stylesheet" href="style.css">
     <!-- MAPY.CZ API -->
    <script src="https://api.mapy.cz/loader.js"></script>
    <script>Loader.async = true;</script>
</head>
<body>
    <div class="game-container">
        <div class="header">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo $isAnonymous ? '🦊' : '👤'; ?>
                </div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
                    <?php if ($isAnonymous): ?>
                        <span class="user-badge">ANONYMNÍ</span>
                    <?php endif; ?>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">Odhlásit se</a>
        </div>
        
        <div class="game-content">
            <h1 class="welcome-message">🎮 Vítej v BOJOVCE, <?php echo htmlspecialchars($userName); ?>!</h1>
            <div class="game-info">
                <p>Tvé dobrodružství začíná zde!</p>
                <p>Pohybuj se po mapě a hledej úkoly ve svém okolí.</p>
            </div>
            <div id="map"></div>
        </div>
    </div>

    <script>
        Loader.load(null, {suggest: true}, function() {
            // Vytvoř mapu se středem na Praze
            var center = SMap.Coords.fromWGS84(14.4378, 50.0755);
            var m = new SMap(JAK.gel("map"), center, 13);
            
            // Přidej ovládací prvky
            m.addDefaultLayer(SMap.DEF_BASE).enable();
            m.addDefaultControls();
            
            // Pokus o získání aktuální polohy uživatele
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var userCoords = SMap.Coords.fromWGS84(
                        position.coords.longitude, 
                        position.coords.latitude
                    );
                    
                    // Vycentruj mapu na uživatele
                    m.setCenterZoom(userCoords, 15);
                    
                    // Přidej značku s polohou uživatele
                    var layer = new SMap.Layer.Marker();
                    m.addLayer(layer);
                    layer.enable();
                    
                    var marker = new SMap.Marker(userCoords, "you", {
                        title: "Vaše poloha"
                    });
                    layer.addMarker(marker);
                }, function(error) {
                    console.log("Chyba geolokace:", error);
                     alert("Nepodařilo se získat vaši polohu. Ujistěte se, že máte povolenou geolokaci v prohlížeči.");
                });
            } else {
                alert("Váš prohlížeč nepodporuje geolokaci.");
            }
        });
    </script>
</body>
</html>
