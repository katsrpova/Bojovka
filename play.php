<?php
session_start();

// Kontrola přihlášení
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

$userName = $_SESSION['user_name'];
$isAnonymous = isset($_SESSION['is_anonymous']) && $_SESSION['is_anonymous'];

// Načti API klíč
require_once 'config.php';
$apiKey = MAPY_CZ_API_KEY;

// Získej ID hry z URL
$gameId = $_GET['id'] ?? null;

if (!$gameId) {
    header('Location: dashboard.php');
    exit;
}

// Mock data pro různé hry (později z databáze)
$games = [
    1 => [
        'id' => 1,
        'title' => 'Prague Castle Adventure',
        'description' => 'Explore the historic Prague Castle area and discover hidden secrets',
        'difficulty' => 'medium',
        'waypoints' => [
            [
                'id' => 1,
                'lat' => 50.0875,
                'lng' => 14.4214,
                'name' => 'Hradčanské náměstí',
                'description' => 'Vítejte na Hradčanském náměstí! Najděte sochu T.G. Masaryka a odpovězte na otázku.',
                'question' => 'V jakém roce byla postavena socha T.G. Masaryka?',
                'answer' => '1928',
                'type' => 'question',
                'completed' => false
            ],
            [
                'id' => 2,
                'lat' => 50.0910,
                'lng' => 14.4016,
                'name' => 'Strahovský klášter',
                'description' => 'Vydejte se ke Strahovskému klášteru. Najděte vstupní bránu.',
                'question' => 'Jaký řád sídlí ve Strahovském klášteře?',
                'answer' => 'premonstrátský',
                'type' => 'question',
                'completed' => false
            ],
            [
                'id' => 3,
                'lat' => 50.0870,
                'lng' => 14.4110,
                'name' => 'Petřínská rozhledna',
                'description' => 'Vyšlapejte si na Petřínskou rozhlednu a vychutnejte si výhled.',
                'question' => 'Kolik metrů měří Petřínská rozhledna?',
                'answer' => '63.5',
                'type' => 'question',
                'completed' => false
            ]
        ]
    ],
    2 => [
        'id' => 2,
        'title' => 'Vltava River Trail',
        'description' => 'Follow the beautiful Vltava river through Prague',
        'difficulty' => 'easy',
        'waypoints' => [
            [
                'id' => 1,
                'lat' => 50.0863,
                'lng' => 14.4113,
                'name' => 'Karlův most',
                'description' => 'Začněte na slavném Karlově mostě.',
                'question' => 'Kolik soch je na Karlově mostě?',
                'answer' => '30',
                'type' => 'question',
                'completed' => false
            ],
            [
                'id' => 2,
                'lat' => 50.0755,
                'lng' => 14.4378,
                'name' => 'Náplavka',
                'description' => 'Pokračujte po nábřeží k Náplavce.',
                'question' => 'Jaká řeka protéká Prahou?',
                'answer' => 'vltava',
                'type' => 'question',
                'completed' => false
            ]
        ]
    ],
    3 => [
        'id' => 3,
        'title' => 'Old Town Mystery',
        'description' => 'Solve puzzles hidden in Prague\'s Old Town',
        'difficulty' => 'hard',
        'waypoints' => [
            [
                'id' => 1,
                'lat' => 50.0875,
                'lng' => 14.4213,
                'name' => 'Staroměstské náměstí',
                'description' => 'Začněte na Staroměstském náměstí u orloje.',
                'question' => 'V jakém století byl postaven Pražský orloj?',
                'answer' => '15',
                'type' => 'question',
                'completed' => false
            ],
            [
                'id' => 2,
                'lat' => 50.0889,
                'lng' => 14.4244,
                'name' => 'Týnský chrám',
                'description' => 'Vydejte se k Týnskému chrámu.',
                'question' => 'Kolik věží má Týnský chrám?',
                'answer' => '2',
                'type' => 'question',
                'completed' => false
            ],
            [
                'id' => 3,
                'lat' => 50.0865,
                'lng' => 14.4208,
                'name' => 'Prašná brána',
                'description' => 'Najděte Prašnou bránu.',
                'question' => 'V jakém roce byla Prašná brána dokončena?',
                'answer' => '1475',
                'type' => 'question',
                'completed' => false
            ]
        ]
    ]
];

// Načti hru podle ID
$game = $games[$gameId] ?? null;

if (!$game) {
    header('Location: dashboard.php');
    exit;
}

// Převod dat na JSON pro JavaScript
$gameJson = json_encode($game, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOJOVKA - <?php echo htmlspecialchars($game['title']); ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="play.css">
</head>
<body>
    <!-- Top Header -->
    <div class="game-header">
        <div class="header-left">
            <button class="btn-back" onclick="confirmExit()">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div class="game-info">
                <h1><?php echo htmlspecialchars($game['title']); ?></h1>
                <div class="game-meta">
                    <span class="progress-indicator">
                        <i class="fas fa-map-marker-alt"></i>
                        <span id="progressText">0/<?php echo count($game['waypoints']); ?></span>
                    </span>
                </div>
            </div>
        </div>
        <div class="header-right">
            <div class="user-avatar-small">
                <?php echo $isAnonymous ? '🦊' : '👤'; ?>
            </div>
        </div>
    </div>

    <!-- Map Container -->
    <div id="map" data-apikey="<?php echo htmlspecialchars($apiKey); ?>"></div>

    <!-- Tasks Button (Bottom Left) -->
    <button class="btn-tasks" id="btnTasks" onclick="toggleTasksPanel()">
        <i class="fas fa-list-check"></i>
        <span>Úkoly</span>
        <span class="tasks-badge" id="tasksBadge">0/<?php echo count($game['waypoints']); ?></span>
    </button>

    <!-- Help Button (Bottom Right) -->
    <button class="btn-help" onclick="toggleHelp()">
        <i class="fas fa-question-circle"></i>
    </button>

    <!-- Bottom Task Panel -->
    <div class="task-panel hidden" id="taskPanel">
        <div class="panel-content">
            <!-- Content will be generated by JavaScript -->
        </div>
    </div>

    <!-- Help Modal -->
    <div class="modal" id="helpModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-question-circle"></i> Nápověda</h3>
                <button class="btn-close" onclick="toggleHelp()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="help-section">
                    <h4><i class="fas fa-map-marked-alt"></i> Jak hrát</h4>
                    <p>Pohybujte se po mapě podle vyznačené trasy. Když se dostanete do blízkosti bodu(okruh 50m), úkol se automaticky odemkne.</p>
                </div>
                <div class="help-section">
                    <h4><i class="fas fa-bullseye"></i> Dokončení úkolu</h4>
                    <p>Klikněte na úkol v dolním panelu, přečtěte si zadání a odpovězte na otázku nebo splňte výzvu.</p>
                </div>
                <div class="help-section">
                    <h4><i class="fas fa-trophy"></i> Body</h4>
                    <p>Za každý splněný úkol získáte body. Čím obtížnější hra, tím více bodů můžete získat.</p>
                </div>
                <div class="help-section">
                    <h4><i class="fas fa-location-arrow"></i> Přesnost GPS</h4>
                    <p>Pro nejlepší zážitek doporučujeme mít zapnutou GPS s vysokou přesností.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal" id="successModal">
        <div class="modal-content success-content">
            <div class="success-animation">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3>Úkol splněn!</h3>
            <p id="successMessage"></p>
            <button class="btn btn-primary" onclick="closeSuccessModal()">
                Pokračovat
            </button>
        </div>
    </div>

    <!-- Completion Modal -->
    <div class="modal" id="completionModal">
        <div class="modal-content completion-content">
            <div class="completion-animation">
                <i class="fas fa-trophy"></i>
            </div>
            <h2>Gratulujeme! 🎉</h2>
            <p>Dokončili jste hru <strong><?php echo htmlspecialchars($game['title']); ?></strong></p>
            <div class="completion-stats">
                <div class="stat">
                    <i class="fas fa-star"></i>
                    <span id="finalPoints">0</span> bodů
                </div>
                <div class="stat">
                    <i class="fas fa-clock"></i>
                    <span id="finalTime">0</span> min
                </div>
            </div>
            <div class="completion-actions">
                <button class="btn btn-secondary" onclick="window.location.href='dashboard.php'">
                    <i class="fas fa-home"></i>
                    Hlavní stránka
                </button>
                <button class="btn btn-primary" onclick="shareResults()">
                    <i class="fas fa-share"></i>
                    Sdílet
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden data for JavaScript -->
    <script>
        const GAME_DATA = <?php echo $gameJson; ?>;
        const API_KEY = '<?php echo $apiKey; ?>';
        const USER_NAME = '<?php echo htmlspecialchars($userName); ?>';
    </script>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Custom JS -->
    <script src="play.js"></script>
</body>
</html>