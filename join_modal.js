/**
 * join_modal.js
 * Logika pro Join modal v dashboard.php
 * - Zadání 6-místného kódu s live ověřením
 * - QR skenování pomocí BarcodeDetector API (nativní) nebo jsQR fallback
 */

// ══════════════════════════════════════
//  MODAL OPEN / CLOSE
// ══════════════════════════════════════
function openJoinModal() {
    const modal = document.getElementById('joinModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    // Fokus na input po animaci
    setTimeout(() => document.getElementById('gameCode')?.focus(), 150);
}

function closeJoinModal() {
    const modal = document.getElementById('joinModal');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
    resetCodeTab();
    stopQrScanner();
}

// ══════════════════════════════════════
//  TAB SWITCHER
// ══════════════════════════════════════
function switchJoinTab(tab) {
    ['code','qr'].forEach(t => {
        document.getElementById(t + 'Content').classList.remove('active');
        document.getElementById('tab' + t.charAt(0).toUpperCase() + t.slice(1))?.classList.remove('active');
    });
    document.getElementById(tab + 'Content').classList.add('active');
    document.getElementById('tab' + tab.charAt(0).toUpperCase() + tab.slice(1))?.classList.add('active');

    if (tab === 'qr') {
        // auto-start scanner
        startQrScanner();
    } else {
        stopQrScanner();
        document.getElementById('gameCode')?.focus();
    }
}

// ══════════════════════════════════════
//  CODE INPUT & VALIDATION
// ══════════════════════════════════════
let validateTimer = null;

function onCodeInput(input) {
    // Uppercase + keep only valid chars (no 0,O,I,1,L to avoid confusion — but we accept all for entry)
    input.value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);

    const code = input.value;
    const btn  = document.getElementById('joinCodeBtn');
    const msg  = document.getElementById('codeValidationMsg');

    // Reset
    input.classList.remove('valid','invalid');
    btn.disabled = true;

    if (code.length === 0) {
        msg.innerHTML = '';
        return;
    }

    if (code.length < 6) {
        msg.innerHTML = `<span class="msg-check">Zadejte ${6 - code.length} ${code.length < 5 ? 'znaků' : 'znak'} navíc…</span>`;
        return;
    }

    // 6 chars — validate with server
    clearTimeout(validateTimer);
    msg.innerHTML = '<span class="msg-check"><i class="fas fa-spinner fa-spin"></i> Ověřuji kód…</span>';

    validateTimer = setTimeout(() => validateCode(code), 500);
}

async function validateCode(code) {
    const input = document.getElementById('gameCode');
    const btn   = document.getElementById('joinCodeBtn');
    const msg   = document.getElementById('codeValidationMsg');

    try {
        const res  = await fetch(`game_code.php?action=validate&code=${encodeURIComponent(code)}`);
        const data = await res.json();

        if (data.valid) {
            input.classList.add('valid');
            msg.innerHTML = `<span class="msg-ok"><i class="fas fa-check-circle"></i> <strong>${escHtml(data.game_title)}</strong> – kód je platný!</span>`;
            btn.disabled = false;
            btn.dataset.gameId = data.game_id;
        } else {
            input.classList.add('invalid');
            msg.innerHTML = `<span class="msg-err"><i class="fas fa-times-circle"></i> ${escHtml(data.error || 'Neplatný kód')}</span>`;
            btn.disabled = true;
        }
    } catch {
        msg.innerHTML = '<span class="msg-err"><i class="fas fa-wifi"></i> Chyba připojení</span>';
    }
}

function joinByCode() {
    const code   = document.getElementById('gameCode').value;
    const gameId = document.getElementById('joinCodeBtn').dataset.gameId;
    if (!code || !gameId) return;
    window.location.href = `play.php?id=${gameId}`;
}

function resetCodeTab() {
    const input = document.getElementById('gameCode');
    if (input) {
        input.value = '';
        input.classList.remove('valid','invalid');
    }
    const msg = document.getElementById('codeValidationMsg');
    if (msg) msg.innerHTML = '';
    const btn = document.getElementById('joinCodeBtn');
    if (btn) btn.disabled = true;
}

// ══════════════════════════════════════
//  QR SCANNER
// ══════════════════════════════════════
let qrStream     = null;
let qrRafId      = null;
let qrDetector   = null;
let qrFoundLock  = false;   // prevent duplicate redirects

async function startQrScanner() {
    const startBtn  = document.getElementById('startScanBtn');
    const stopBtn   = document.getElementById('stopScanBtn');
    const statusEl  = document.getElementById('scanStatus');
    const video     = document.getElementById('qrVideo');

    if (!video) return;

    // Check camera permission
    if (!navigator.mediaDevices?.getUserMedia) {
        statusEl.innerHTML = '<i class="fas fa-times-circle" style="color:#EF4444"></i> Prohlížeč nepodporuje přístup ke kameře.';
        return;
    }

    try {
        qrStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 1280 } }
        });
        video.srcObject = qrStream;
        await video.play();

        startBtn.style.display = 'none';
        stopBtn.style.display  = 'block';
        statusEl.innerHTML = '<i class="fas fa-circle" style="color:#10B981;animation:blink 1s infinite"></i> Skener aktivní…';

        // Pick detection method
        if ('BarcodeDetector' in window) {
            // Native Chrome/Edge/Android
            qrDetector = new BarcodeDetector({ formats: ['qr_code'] });
            detectNative(video);
        } else {
            // jsQR fallback — load lazily
            await loadJsQR();
            detectJsQR(video);
        }
    } catch (err) {
        let errMsg = 'Nepodařilo se zapnout kameru.';
        if (err.name === 'NotAllowedError') errMsg = 'Přístup ke kameře byl zamítnut. Povolte kameru v nastavení prohlížeče.';
        if (err.name === 'NotFoundError')   errMsg = 'Kamera nebyla nalezena.';
        statusEl.innerHTML = `<i class="fas fa-times-circle" style="color:#EF4444"></i> ${errMsg}`;
    }
}

function stopQrScanner() {
    if (qrStream) {
        qrStream.getTracks().forEach(t => t.stop());
        qrStream = null;
    }
    if (qrRafId) {
        cancelAnimationFrame(qrRafId);
        qrRafId = null;
    }
    qrFoundLock = false;

    const video    = document.getElementById('qrVideo');
    const startBtn = document.getElementById('startScanBtn');
    const stopBtn  = document.getElementById('stopScanBtn');
    const statusEl = document.getElementById('scanStatus');

    if (video)    video.srcObject = null;
    if (startBtn) startBtn.style.display = 'block';
    if (stopBtn)  stopBtn.style.display  = 'none';
    if (statusEl) statusEl.innerHTML = '<i class="fas fa-camera" style="margin-right:6px"></i>Namiřte kameru na QR kód';
}

// Native BarcodeDetector
function detectNative(video) {
    async function tick() {
        if (!qrStream || qrFoundLock) return;
        try {
            const barcodes = await qrDetector.detect(video);
            if (barcodes.length > 0) {
                onQrDetected(barcodes[0].rawValue);
                return;
            }
        } catch { /* frame not ready */ }
        qrRafId = requestAnimationFrame(tick);
    }
    qrRafId = requestAnimationFrame(tick);
}

// jsQR fallback
function detectJsQR(video) {
    const canvas = document.createElement('canvas');
    const ctx    = canvas.getContext('2d', { willReadFrequently: true });

    function tick() {
        if (!qrStream || qrFoundLock) return;
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            canvas.width  = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0);
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const code = window.jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: 'dontInvert',
            });
            if (code) {
                onQrDetected(code.data);
                return;
            }
        }
        qrRafId = requestAnimationFrame(tick);
    }
    qrRafId = requestAnimationFrame(tick);
}

async function loadJsQR() {
    if (window.jsQR) return; // already loaded
    return new Promise((resolve, reject) => {
        const s  = document.createElement('script');
        s.src    = 'https://cdnjs.cloudflare.com/ajax/libs/jsQR/1.4.0/jsQR.min.js';
        s.onload = resolve;
        s.onerror = () => reject(new Error('Nepodařilo se načíst jsQR'));
        document.head.appendChild(s);
    });
}

// ══════════════════════════════════════
//  QR RESULT HANDLER
// ══════════════════════════════════════
async function onQrDetected(rawValue) {
    if (qrFoundLock) return;
    qrFoundLock = true;

    stopQrScanner();

    const statusEl = document.getElementById('scanStatus');
    statusEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> QR detekován, ověřuji…';

    // Extract code from URL or use raw as code
    let code = extractCodeFromValue(rawValue);

    if (!code) {
        statusEl.innerHTML = `<i class="fas fa-times-circle" style="color:#EF4444"></i> QR kód neobsahuje platný herní kód.`;
        setTimeout(() => { qrFoundLock = false; }, 3000);
        return;
    }

    // Validate
    try {
        const res  = await fetch(`game_code.php?action=validate&code=${encodeURIComponent(code)}`);
        const data = await res.json();

        if (data.valid) {
            statusEl.innerHTML = `<i class="fas fa-check-circle" style="color:#10B981"></i> Hra nalezena: <strong>${escHtml(data.game_title)}</strong>. Přesměrovávám…`;
            setTimeout(() => {
                window.location.href = `play.php?id=${data.game_id}`;
            }, 800);
        } else {
            statusEl.innerHTML = `<i class="fas fa-times-circle" style="color:#EF4444"></i> ${escHtml(data.error || 'Neplatný kód')}`;
            setTimeout(() => {
                qrFoundLock = false;
                document.getElementById('startScanBtn').style.display = 'block';
                document.getElementById('stopScanBtn').style.display  = 'none';
            }, 3000);
        }
    } catch {
        statusEl.innerHTML = '<i class="fas fa-wifi" style="color:#EF4444"></i> Chyba připojení při ověřování.';
        setTimeout(() => { qrFoundLock = false; }, 3000);
    }
}

/**
 * Extrahuje kód z URL (join.php?code=ABC123) nebo vrátí raw pokud vypadá jako kód
 */
function extractCodeFromValue(raw) {
    // Try URL parse
    try {
        const url    = new URL(raw);
        const code   = url.searchParams.get('code');
        if (code && /^[A-Z0-9]{6}$/i.test(code)) return code.toUpperCase();
    } catch { /* not a URL */ }

    // Raw might be just a 6-char code
    const trimmed = raw.trim().toUpperCase();
    if (/^[A-Z0-9]{6}$/.test(trimmed)) return trimmed;

    return null;
}

// ══════════════════════════════════════
//  UTILS
// ══════════════════════════════════════
function escHtml(str) {
    const el = document.createElement('span');
    el.textContent = str;
    return el.innerHTML;
}

// Close on outside click
document.addEventListener('click', e => {
    const modal = document.getElementById('joinModal');
    if (e.target === modal) closeJoinModal();
});

// Close on ESC
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeJoinModal();
    // Submit on Enter in code input
    if (e.key === 'Enter' && document.activeElement?.id === 'gameCode') {
        const btn = document.getElementById('joinCodeBtn');
        if (!btn.disabled) joinByCode();
    }
});