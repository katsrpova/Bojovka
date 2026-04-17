<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit;
}

require_once 'config.php';

$gameId    = intval($_GET['id'] ?? 0);
$userName  = $_SESSION['user_name'];
$isAnonymous = isset($_SESSION['is_anonymous']) && $_SESSION['is_anonymous'];

if (!$gameId) {
    header('Location: dashboard.php');
    exit;
}

// Mock data (v produkci z DB)
$games = [
    1 => ['id' => 1, 'title' => 'Prague Castle Adventure', 'difficulty' => 'medium', 'waypoints' => 3],
    2 => ['id' => 2, 'title' => 'Vltava River Trail',      'difficulty' => 'easy',   'waypoints' => 2],
    3 => ['id' => 3, 'title' => 'Old Town Mystery',        'difficulty' => 'hard',   'waypoints' => 3],
];

$game = $games[$gameId] ?? null;
if (!$game) {
    header('Location: dashboard.php');
    exit;
}

$playUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
         . '://' . $_SERVER['HTTP_HOST']
         . dirname($_SERVER['PHP_SELF'])
         . '/play.php?id=' . $gameId;
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BOJOVKA – Sdílet hru</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- QRCode.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --brown:     #8B4513;
            --brown-lt:  #D2691E;
            --amber:     #f7931e;
            --sand:      #F5F0E8;
            --ink:       #1a1208;
            --muted:     #6b5a44;
            --border:    #ddd0bb;
            --green:     #2d9e5f;
            --red:       #c94040;
        }

        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap');

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--sand);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── HEADER ── */
        .top-bar {
            background: linear-gradient(135deg, var(--brown), var(--brown-lt));
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,.15);
        }
        .top-bar a {
            color: rgba(255,255,255,.85);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            transition: color .2s;
        }
        .top-bar a:hover { color: #fff; }
        .top-bar h1 {
            color: #fff;
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            letter-spacing: .5px;
            flex: 1;
        }

        /* ── MAIN ── */
        .page {
            flex: 1;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 30px 16px 60px;
        }

        .card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 30px rgba(139,69,19,.12);
            width: 100%;
            max-width: 480px;
            overflow: hidden;
        }

        /* GAME BANNER */
        .game-banner {
            background: linear-gradient(135deg, var(--brown), var(--brown-lt));
            padding: 24px;
            text-align: center;
            color: #fff;
        }
        .game-banner .compass { font-size: 40px; margin-bottom: 8px; }
        .game-banner h2 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            margin-bottom: 6px;
        }
        .game-banner .meta {
            font-size: 13px;
            opacity: .85;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        /* BODY */
        .card-body { padding: 28px 24px; }

        /* CODE SECTION */
        .section-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 10px;
        }

        .code-display {
            background: var(--sand);
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }
        .code-display .big-code {
            font-family: 'Courier New', monospace;
            font-size: 42px;
            font-weight: 700;
            letter-spacing: 10px;
            color: var(--ink);
            display: block;
            margin-bottom: 8px;
        }
        .code-display .code-hint {
            font-size: 12px;
            color: var(--muted);
        }
        .code-display .code-expire {
            position: absolute;
            top: 8px; right: 10px;
            font-size: 11px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .code-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 28px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            border: none;
            border-radius: 9px;
            padding: 11px 18px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
            font-family: inherit;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--brown), var(--brown-lt));
            color: #fff;
            flex: 1;
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(139,69,19,.3); }
        .btn-outline {
            background: #fff;
            color: var(--brown);
            border: 2px solid var(--border);
            flex: 1;
        }
        .btn-outline:hover { border-color: var(--brown); background: var(--sand); }
        .btn-danger {
            background: #fff;
            color: var(--red);
            border: 2px solid #f5c6c6;
        }
        .btn-danger:hover { background: #fff0f0; border-color: var(--red); }
        .btn:disabled {
            opacity: .45;
            cursor: not-allowed;
            transform: none !important;
        }

        /* DIVIDER */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--border);
            font-size: 12px;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 24px;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* QR SECTION */
        .qr-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
        }
        #qrcode {
            padding: 16px;
            background: #fff;
            border: 2px solid var(--border);
            border-radius: 14px;
            display: inline-block;
        }
        #qrcode canvas, #qrcode img { display: block; }

        .qr-download-btn {
            font-size: 13px;
        }

        /* STATUS PILL */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active { background: #e6f7ee; color: var(--green); }
        .status-inactive { background: #fceaea; color: var(--red); }
        .status-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: currentColor;
            animation: blink 1.5s infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }

        /* TOAST */
        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            background: var(--ink);
            color: #fff;
            padding: 12px 22px;
            border-radius: 10px;
            font-size: 14px;
            opacity: 0;
            pointer-events: none;
            transition: all .3s;
            z-index: 9999;
            white-space: nowrap;
        }
        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* JOIN COUNTER */
        .join-counter {
            text-align: center;
            font-size: 13px;
            color: var(--muted);
            margin-top: 4px;
        }
        .join-counter strong { color: var(--ink); font-size: 16px; }

        /* LOADING SPINNER */
        .spinner {
            width: 20px; height: 20px;
            border: 2px solid rgba(255,255,255,.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
            display: inline-block;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 480px) {
            .card-body { padding: 20px 16px; }
            .code-display .big-code { font-size: 32px; letter-spacing: 6px; }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Zpět</a>
    <h1>🧭 BOJOVKA</h1>
</div>

<div class="page">
    <div class="card">
        <!-- Game banner -->
        <div class="game-banner">
            <div class="compass">🗺️</div>
            <h2><?php echo htmlspecialchars($game['title']); ?></h2>
            <div class="meta">
                <span><i class="fas fa-map-marker-alt"></i> <?php echo $game['waypoints']; ?> úkolů</span>
                <span><i class="fas fa-gauge-high"></i> <?php echo $game['difficulty']; ?></span>
            </div>
        </div>

        <div class="card-body">
            <!-- Status + Join counter -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <span class="status-pill status-active" id="statusPill">
                    <span class="status-dot"></span>
                    Aktivní
                </span>
                <span class="join-counter">Připojilo se: <strong id="joinCount">–</strong></span>
            </div>

            <!-- CODE -->
            <div class="section-label">Herní kód</div>
            <div class="code-display" id="codeDisplay">
                <span class="code-expire" id="codeExpire"></span>
                <span class="big-code" id="bigCode">
                    <span class="spinner"></span>
                </span>
                <span class="code-hint">Hráči zadají tento kód v menu „Připojit se"</span>
            </div>

            <div class="code-actions">
                <button class="btn btn-primary" onclick="copyCode()" id="copyBtn" disabled>
                    <i class="fas fa-copy"></i> Zkopírovat kód
                </button>
                <button class="btn btn-outline" onclick="generateNewCode()" id="regenBtn">
                    <i class="fas fa-rotate"></i> Nový kód
                </button>
            </div>

            <!-- DIVIDER -->
            <div class="divider">nebo sdílej QR</div>

            <!-- QR CODE -->
            <div class="qr-wrapper">
                <div id="qrcode"></div>
                <button class="btn btn-outline qr-download-btn" onclick="downloadQR()" id="dlBtn" disabled>
                    <i class="fas fa-download"></i> Stáhnout QR
                </button>
            </div>

            <!-- RELEASE CODE -->
            <div style="text-align:center;">
                <button class="btn btn-danger" onclick="releaseCode()" id="releaseBtn" disabled>
                    <i class="fas fa-ban"></i> Deaktivovat kód
                </button>
            </div>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
const GAME_ID    = <?php echo $gameId; ?>;
const GAME_TITLE = <?php echo json_encode($game['title']); ?>;

let currentCode = null;
let qrInstance  = null;

// ── INIT ──────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    generateOrLoadCode();
    // Poll join count every 10 s
    setInterval(pollStatus, 10000);
});

// ── GENERATE / LOAD CODE ──────────────────────────────────────────
async function generateOrLoadCode() {
    try {
        const res = await fetch('game_code.php?action=generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `game_id=${GAME_ID}&game_title=${encodeURIComponent(GAME_TITLE)}`,
        });
        const data = await res.json();

        if (!data.success) throw new Error(data.error || 'Chyba serveru');

        currentCode = data.code;
        renderCode(data);
        buildQR();
        enableButtons();
    } catch (err) {
        document.getElementById('bigCode').textContent = 'CHYBA';
        showToast('❌ ' + err.message, 4000);
    }
}

function renderCode(data) {
    document.getElementById('bigCode').textContent = data.code;

    // Expiration label
    if (data.expires_at) {
        const exp = new Date(data.expires_at * 1000);
        const h = exp.getHours().toString().padStart(2,'0');
        const m = exp.getMinutes().toString().padStart(2,'0');
        document.getElementById('codeExpire').innerHTML =
            `<i class="fas fa-clock"></i> Do ${h}:${m}`;
    }
}

function enableButtons() {
    ['copyBtn','regenBtn','releaseBtn','dlBtn'].forEach(id => {
        document.getElementById(id).disabled = false;
    });
}

// ── QR CODE ───────────────────────────────────────────────────────
function buildQR() {
    const container = document.getElementById('qrcode');
    container.innerHTML = '';

    // URL, která přesměruje hráče přímo na hru (přes validaci kódu)
    const joinUrl = window.location.origin
        + window.location.pathname.replace('qr_share.php','')
        + `join.php?code=${currentCode}`;

    qrInstance = new QRCode(container, {
        text:          joinUrl,
        width:         200,
        height:        200,
        colorDark:     '#1a1208',
        colorLight:    '#ffffff',
        correctLevel:  QRCode.CorrectLevel.H,
    });
}

function downloadQR() {
    const canvas = document.querySelector('#qrcode canvas');
    if (!canvas) return showToast('QR kód ještě není připraven');

    // Přidej bílý rámeček + text
    const size    = canvas.width + 60;
    const offscreen = document.createElement('canvas');
    offscreen.width  = size;
    offscreen.height = size + 50;

    const ctx = offscreen.getContext('2d');
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, offscreen.width, offscreen.height);
    ctx.drawImage(canvas, 30, 20);

    ctx.fillStyle = '#1a1208';
    ctx.font = 'bold 18px monospace';
    ctx.textAlign = 'center';
    ctx.fillText(currentCode, offscreen.width / 2, size + 35);

    const link = document.createElement('a');
    link.href     = offscreen.toDataURL('image/png');
    link.download = `bojovka-${currentCode}.png`;
    link.click();
    showToast('✅ QR stažen');
}

// ── COPY CODE ─────────────────────────────────────────────────────
function copyCode() {
    if (!currentCode) return;
    navigator.clipboard.writeText(currentCode)
        .then(() => showToast('✅ Kód zkopírován: ' + currentCode))
        .catch(() => {
            // Fallback
            const el = document.createElement('textarea');
            el.value = currentCode;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            el.remove();
            showToast('✅ Kód zkopírován');
        });
}

// ── GENERATE NEW CODE ─────────────────────────────────────────────
async function generateNewCode() {
    if (!confirm('Generovat nový kód? Starý kód přestane fungovat.')) return;

    // Uvolni starý
    if (currentCode) {
        await fetch('game_code.php?action=release', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `code=${currentCode}`,
        });
    }
    currentCode = null;
    document.getElementById('bigCode').innerHTML = '<span class="spinner"></span>';
    document.getElementById('qrcode').innerHTML  = '';
    ['copyBtn','regenBtn','releaseBtn','dlBtn'].forEach(id => {
        document.getElementById(id).disabled = true;
    });

    await generateOrLoadCode();
}

// ── RELEASE CODE ──────────────────────────────────────────────────
async function releaseCode() {
    if (!currentCode) return;
    if (!confirm('Opravdu deaktivovat kód? Hráči se pak nebudou moci připojit přes tento kód.')) return;

    try {
        const res = await fetch('game_code.php?action=release', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `code=${currentCode}`,
        });
        const data = await res.json();

        if (data.success) {
            document.getElementById('statusPill').className = 'status-pill status-inactive';
            document.getElementById('statusPill').innerHTML =
                '<span class="status-dot" style="animation:none"></span> Neaktivní';
            showToast('Kód byl deaktivován');
            ['copyBtn','releaseBtn'].forEach(id => {
                document.getElementById(id).disabled = true;
            });
        } else {
            showToast('❌ ' + (data.error || 'Chyba'));
        }
    } catch {
        showToast('❌ Chyba připojení');
    }
}

// ── POLL STATUS ───────────────────────────────────────────────────
async function pollStatus() {
    if (!currentCode) return;
    try {
        const res  = await fetch(`game_code.php?action=status&code=${currentCode}`);
        const data = await res.json();
        if (data.found) {
            document.getElementById('joinCount').textContent = data.join_count ?? 0;
        }
    } catch { /* silent */ }
}

// ── TOAST ─────────────────────────────────────────────────────────
function showToast(msg, duration = 2500) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), duration);
}
</script>
</body>
</html>