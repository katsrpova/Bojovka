// ==================== GLOBÁLNÍ PROMĚNNÉ ====================
let map;
let userLocationMarker = null;
let userLocationCircle = null;
let routeLine = null;
let markers = [];
let currentTaskIndex = 0;
let completedTasks = 0;
let startTime = Date.now();
let watchId = null;
let userPosition = null;

// Konstanta pro vzdálenost odemknutí úkolu (v metrech)
const UNLOCK_DISTANCE = 50;

// ==================== INICIALIZACE ====================
document.addEventListener('DOMContentLoaded', function() {
    initMap();
    initTaskList();
    initEventListeners();
    startLocationTracking();
});

// ==================== MAPA ====================
function initMap() {
    try {
        console.log('Initializing Leaflet map...');
        
        // Výchozí centrum - první waypoint
        const firstWaypoint = GAME_DATA.waypoints[0];
        map = L.map('map').setView([firstWaypoint.lat, firstWaypoint.lng], 15);

        // Přidej Mapy.cz tile layer
        L.tileLayer('https://api.mapy.cz/v1/maptiles/basic/256/{z}/{x}/{y}?apikey=' + API_KEY, {
            minZoom: 0,
            maxZoom: 19,
            attribution: '<a href="https://api.mapy.cz/copyright" target="_blank">&copy; Seznam.cz a.s. a další</a>',
        }).addTo(map);

        // Logo Mapy.cz
        const LogoControl = L.Control.extend({
            options: { position: 'bottomleft' },
            onAdd: function (map) {
                const container = L.DomUtil.create('div');
                const link = L.DomUtil.create('a', '', container);
                link.setAttribute('href', 'http://mapy.cz/');
                link.setAttribute('target', '_blank');
                link.innerHTML = '<img src="https://api.mapy.cz/img/api/logo.svg" />';
                L.DomEvent.disableClickPropagation(link);
                return container;
            },
        });
        new LogoControl().addTo(map);

        // Přidej waypoint markery
        addWaypointMarkers();

        // Naplánuj trasu
        planRoute();

        console.log('Map initialized successfully');

    } catch (error) {
        console.error('Error initializing map:', error);
    }
}

function addWaypointMarkers() {
    GAME_DATA.waypoints.forEach((waypoint, index) => {
        const isCompleted = waypoint.completed;
        const isActive = index === currentTaskIndex;
        
        let markerClass = 'custom-marker';
        if (isCompleted) markerClass += ' marker-completed';
        if (isActive) markerClass += ' marker-active';
        
        const icon = L.divIcon({
            className: markerClass,
            html: `
                <div class="marker-pin">
                    <div class="marker-pin-inner">
                        <div class="marker-number">${index + 1}</div>
                    </div>
                </div>
            `,
            iconSize: [35, 50],
            iconAnchor: [17, 50]
        });

        const marker = L.marker([waypoint.lat, waypoint.lng], { icon: icon }).addTo(map);
        marker.bindPopup(`<strong>${waypoint.name}</strong>`);
        markers.push(marker);
    });
}

async function planRoute() {
    if (GAME_DATA.waypoints.length < 2) return;

    try {
        const start = `${GAME_DATA.waypoints[0].lng},${GAME_DATA.waypoints[0].lat}`;
        const end = `${GAME_DATA.waypoints[GAME_DATA.waypoints.length - 1].lng},${GAME_DATA.waypoints[GAME_DATA.waypoints.length - 1].lat}`;
        
        let waypointsParam = '';
        if (GAME_DATA.waypoints.length > 2) {
            const middlePoints = GAME_DATA.waypoints.slice(1, -1)
                .map(wp => `${wp.lng},${wp.lat}`)
                .join(';');
            waypointsParam = `&waypoints=${encodeURIComponent(middlePoints)}`;
        }
        
        const url = `https://api.mapy.cz/v1/routing/route?` +
            `apikey=${API_KEY}` +
            `&start=${start}` +
            `&end=${end}` +
            `${waypointsParam}` +
            `&routeType=foot_fast` +
            `&format=geojson`;
        
        const response = await fetch(url);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        
        const data = await response.json();
        displayRoute(data);
        
    } catch (error) {
        console.error('Error planning route:', error);
    }
}

function displayRoute(routeData) {
    if (routeLine) {
        map.removeLayer(routeLine);
    }
    
    routeLine = L.geoJSON(routeData.geometry, {
        style: {
            color: '#8B4513',
            weight: 4,
            opacity: 0.7
        }
    }).addTo(map);
    
    const bounds = routeLine.getBounds();
    map.fitBounds(bounds, { padding: [50, 50] });
}

// ==================== SLEDOVÁNÍ POLOHY ====================
function startLocationTracking() {
    if (navigator.geolocation) {
        watchId = navigator.geolocation.watchPosition(
            updateUserLocation,
            handleLocationError,
            {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            }
        );
    } else {
        alert('Váš prohlížeč nepodporuje geolokaci.');
    }
}

function updateUserLocation(position) {
    const lat = position.coords.latitude;
    const lon = position.coords.longitude;
    const accuracy = position.coords.accuracy;
    
    userPosition = { lat, lon };
    
    // Aktualizuj nebo vytvoř marker pro polohu uživatele
    if (userLocationMarker) {
        userLocationMarker.setLatLng([lat, lon]);
        userLocationCircle.setLatLng([lat, lon]);
        userLocationCircle.setRadius(accuracy);
    } else {
        const userIcon = L.divIcon({
            className: 'user-location-marker',
            html: '<div class="user-location-dot"></div>',
            iconSize: [20, 20],
            iconAnchor: [10, 10]
        });
        
        userLocationMarker = L.marker([lat, lon], { icon: userIcon }).addTo(map);
        userLocationMarker.bindPopup('<strong>Vaše poloha</strong>');
        
        userLocationCircle = L.circle([lat, lon], {
            radius: accuracy,
            color: '#4285F4',
            fillColor: '#4285F4',
            fillOpacity: 0.1,
            weight: 1
        }).addTo(map);
        
        // Při prvním získání pozice vycentruj mapu
        map.setView([lat, lon], 16);
    }
    
    // Zkontroluj vzdálenost k aktuálnímu úkolu
    checkTaskProximity();
}

function handleLocationError(error) {
    console.error('Geolocation error:', error.message);
}

function checkTaskProximity() {
    if (!userPosition || currentTaskIndex >= GAME_DATA.waypoints.length) return;
    
    const currentTask = GAME_DATA.waypoints[currentTaskIndex];
    if (currentTask.completed) return;
    
    const distance = calculateDistance(
        userPosition.lat,
        userPosition.lon,
        currentTask.lat,
        currentTask.lng
    );
    
    // Aktualizuj status v task card
    updateTaskStatus(currentTaskIndex, distance);
    
    // Pokud je uživatel dostatečně blízko, odemkni úkol
    if (distance <= UNLOCK_DISTANCE && !currentTask.unlocked) {
        unlockTask(currentTaskIndex);
    }
}

function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // poloměr Země v metrech
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
            Math.cos(φ1) * Math.cos(φ2) *
            Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
}

function unlockTask(taskIndex) {
    GAME_DATA.waypoints[taskIndex].unlocked = true;
    
    // Aktualizuj marker
    updateMarker(taskIndex);
    
    // Aktualizuj task card
    renderTaskList();
    
    // Zobraz notifikaci
    showNotification(`Úkol "${GAME_DATA.waypoints[taskIndex].name}" je nyní odemčen!`);
}

function updateMarker(taskIndex) {
    const waypoint = GAME_DATA.waypoints[taskIndex];
    const isCompleted = waypoint.completed;
    const isActive = taskIndex === currentTaskIndex;
    
    let markerClass = 'custom-marker';
    if (isCompleted) markerClass += ' marker-completed';
    if (isActive) markerClass += ' marker-active';
    
    const icon = L.divIcon({
        className: markerClass,
        html: `
            <div class="marker-pin">
                <div class="marker-pin-inner">
                    <div class="marker-number">${taskIndex + 1}</div>
                </div>
            </div>
        `,
        iconSize: [35, 50],
        iconAnchor: [17, 50]
    });
    
    markers[taskIndex].setIcon(icon);
}

// ==================== SEZNAM ÚKOLŮ ====================
function initTaskList() {
    renderTaskList();
}

function renderTaskList() {
    const panelContent = document.querySelector('.panel-content');
    
    const tasksHTML = GAME_DATA.waypoints.map((waypoint, index) => {
        const isCompleted = waypoint.completed;
        const isActive = index === currentTaskIndex;
        const isLocked = !waypoint.unlocked && !isCompleted && index > 0;
        
        let statusText = '';
        let statusIcon = '';
        let cardClass = 'task-card';
        
        if (isCompleted) {
            cardClass += ' completed';
            statusText = 'Dokončeno';
            statusIcon = '<i class="fas fa-check-circle task-card-icon"></i>';
        } else if (isActive && waypoint.unlocked) {
            cardClass += ' active';
            statusText = 'Dostupný';
            statusIcon = '<i class="fas fa-circle-dot task-card-icon"></i>';
        } else if (isLocked) {
            cardClass += ' locked';
            statusText = 'Zamčeno - přibliž se';
            statusIcon = '<i class="fas fa-lock task-card-icon"></i>';
        } else {
            statusText = 'Čeká na dokončení';
            statusIcon = '<i class="fas fa-circle task-card-icon"></i>';
        }
        
        return `
            <div class="${cardClass}" onclick="${isLocked ? '' : `openTaskDetail(${index})`}">
                <div class="task-number-badge">${index + 1}</div>
                <div class="task-card-content">
                    <div class="task-card-title">${waypoint.name}</div>
                    <div class="task-card-status">
                        ${statusText}
                    </div>
                </div>
                ${statusIcon}
            </div>
        `;
    }).join('');
    
    panelContent.innerHTML = `
        <div class="task-list-view" id="taskListView">
            <h2>
                <div class="task-list-header">
                    <i class="fas fa-list-check"></i>
                    <span>Úkoly</span>
                </div>
                <button class="btn-close-panel" onclick="closeTasksPanel()">
                    <i class="fas fa-times"></i>
                </button>
            </h2>
            <div class="tasks-container" id="tasksContainer">
                ${tasksHTML}
            </div>
        </div>
        <div class="task-detail-view" id="taskDetailView" style="display: none;">
            <button class="btn-back-to-list" onclick="showTaskList()">
                <i class="fas fa-arrow-left"></i>
                Zpět na seznam
            </button>
            <div id="taskDetailContent"></div>
        </div>
    `;
    
    updateProgress();
}

function updateTaskStatus(taskIndex, distance) {
    const distanceText = distance < 1000 
        ? `${Math.round(distance)} m` 
        : `${(distance / 1000).toFixed(1)} km`;
    
    // Tato funkce může být rozšířena pro real-time update vzdálenosti
}

function updateProgress() {
    const completed = GAME_DATA.waypoints.filter(w => w.completed).length;
    const total = GAME_DATA.waypoints.length;
    document.getElementById('progressText').textContent = `${completed}/${total}`;
    
    // Update tasks badge
    const badge = document.getElementById('tasksBadge');
    if (badge) {
        badge.textContent = `${completed}/${total}`;
    }
}

// ==================== TASKS PANEL TOGGLE ====================
function toggleTasksPanel() {
    const panel = document.getElementById('taskPanel');
    panel.classList.toggle('hidden');
}

function closeTasksPanel() {
    const panel = document.getElementById('taskPanel');
    panel.classList.add('hidden');
}

// ==================== DETAIL ÚKOLU ====================
// ==================== DETAIL ÚKOLU ====================
function showTaskList() {
    document.getElementById('taskDetailView').style.display = 'none';
    document.getElementById('taskListView').style.display = 'block';
    
    // Vrať mapu na celkový pohled
    if (routeLine) {
        const bounds = routeLine.getBounds();
        map.fitBounds(bounds, { padding: [50, 50] });
    }
}

function openTaskDetail(taskIndex) {
    const waypoint = GAME_DATA.waypoints[taskIndex];
    
    if (waypoint.completed) {
        showCompletedTaskDetail(waypoint, taskIndex);
    } else if (waypoint.unlocked || taskIndex === 0) {
        showActiveTaskDetail(waypoint, taskIndex);
    } else {
        return; // Locked task
    }
    
    // Přepni na detail view
    document.getElementById('taskListView').style.display = 'none';
    document.getElementById('taskDetailView').style.display = 'block';
    
    // Zaměř mapu na waypoint
    map.setView([waypoint.lat, waypoint.lng], 17);
}

function showActiveTaskDetail(waypoint, taskIndex) {
    const content = document.getElementById('taskDetailContent');
    
    content.innerHTML = `
        <div class="task-detail-header">
            <h2 class="task-detail-title">${waypoint.name}</h2>
            <div class="task-detail-location">
                <i class="fas fa-map-marker-alt"></i>
                <span>Úkol ${taskIndex + 1} z ${GAME_DATA.waypoints.length}</span>
            </div>
        </div>
        
        <div class="task-detail-description">
            ${waypoint.description}
        </div>
        
        <div class="task-question-section">
            <div class="question-label">
                <i class="fas fa-circle-question"></i>
                ${waypoint.question}
            </div>
            <input 
                type="text" 
                class="answer-input" 
                id="answerInput" 
                placeholder="Zadejte odpověď..."
            >
            <button class="btn btn-primary" onclick="submitAnswer(${taskIndex})">
                <i class="fas fa-paper-plane"></i>
                Odeslat odpověď
            </button>
        </div>
    `;
}

function showCompletedTaskDetail(waypoint, taskIndex) {
    const content = document.getElementById('taskDetailContent');
    
    content.innerHTML = `
        <div class="task-detail-header">
            <h2 class="task-detail-title">${waypoint.name}</h2>
            <div class="task-detail-location">
                <i class="fas fa-map-marker-alt"></i>
                <span>Úkol ${taskIndex + 1} z ${GAME_DATA.waypoints.length}</span>
            </div>
        </div>
        
        <div class="task-completed-message">
            <i class="fas fa-check-circle"></i>
            <h3>Úkol dokončen!</h3>
            <p>Tento úkol jste již splnili.</p>
        </div>
    `;
}

function showTaskList() {
    document.getElementById('taskDetailView').style.display = 'none';
    document.getElementById('taskListView').style.display = 'block';
    
    // Vrať mapu na celkový pohled
    if (routeLine) {
        const bounds = routeLine.getBounds();
        map.fitBounds(bounds, { padding: [50, 50] });
    }
}

// ==================== ODPOVĚDI NA ÚKOLY ====================
function submitAnswer(taskIndex) {
    const answerInput = document.getElementById('answerInput');
    const userAnswer = answerInput.value.trim().toLowerCase();
    const correctAnswer = GAME_DATA.waypoints[taskIndex].answer.toLowerCase();
    
    if (!userAnswer) {
        alert('Prosím zadejte odpověď!');
        return;
    }
    
    // Zkontroluj odpověď (case-insensitive, odstranění diakritiky)
    const normalizedUserAnswer = removeAccents(userAnswer);
    const normalizedCorrectAnswer = removeAccents(correctAnswer);
    
    if (normalizedUserAnswer === normalizedCorrectAnswer) {
        completeTask(taskIndex);
    } else {
        // Nesprávná odpověď
        answerInput.value = '';
        answerInput.style.borderColor = '#dc3545';
        setTimeout(() => {
            answerInput.style.borderColor = '';
        }, 1000);
        showNotification('Nesprávná odpověď. Zkus to znovu!');
    }
}

function removeAccents(str) {
    return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

function completeTask(taskIndex) {
    GAME_DATA.waypoints[taskIndex].completed = true;
    completedTasks++;
    
    // Aktualizuj marker
    updateMarker(taskIndex);
    
    // Pokud nejde o poslední úkol, nastav další jako aktuální
    if (taskIndex < GAME_DATA.waypoints.length - 1) {
        currentTaskIndex = taskIndex + 1;
        // První úkol je vždy odemčen
        if (currentTaskIndex === 0) {
            GAME_DATA.waypoints[0].unlocked = true;
        }
    }
    
    // Aktualizuj seznam
    renderTaskList();
    
    // Vrať se na seznam
    showTaskList();
    
    // Zobraz success modal
    showSuccessModal(taskIndex);
    
    // Zkontroluj, zda je hra dokončena
    if (completedTasks === GAME_DATA.waypoints.length) {
        setTimeout(() => {
            showCompletionModal();
        }, 2000);
    }
}

// ==================== MODALY ====================
function toggleHelp() {
    const modal = document.getElementById('helpModal');
    modal.classList.toggle('active');
}

function showSuccessModal(taskIndex) {
    const modal = document.getElementById('successModal');
    const message = document.getElementById('successMessage');
    
    message.textContent = `Správně! Pokračuj k dalšímu úkolu.`;
    
    modal.classList.add('active');
}

function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    modal.classList.remove('active');
}

function showCompletionModal() {
    const modal = document.getElementById('completionModal');
    
    // Vypočítej čas
    const timeElapsed = Math.round((Date.now() - startTime) / 60000); // v minutách
    document.getElementById('finalTime').textContent = timeElapsed;
    
    // Vypočítej body
    const basePoints = 100;
    const difficultyMultiplier = GAME_DATA.difficulty === 'easy' ? 1 : GAME_DATA.difficulty === 'medium' ? 1.5 : 2;
    const finalPoints = Math.round(basePoints * GAME_DATA.waypoints.length * difficultyMultiplier);
    document.getElementById('finalPoints').textContent = finalPoints;
    
    modal.classList.add('active');
    
    // Zastav sledování polohy
    if (watchId) {
        navigator.geolocation.clearWatch(watchId);
    }
}

function shareResults() {
    const timeElapsed = Math.round((Date.now() - startTime) / 60000);
    const text = `Dokončil(a) jsem hru "${GAME_DATA.title}" v BOJOVKA! 🎉\nČas: ${timeElapsed} min\n#BOJOVKA #Adventure`;
    
    if (navigator.share) {
        navigator.share({
            title: 'BOJOVKA - Dokončená hra',
            text: text,
        }).catch(err => console.log('Error sharing:', err));
    } else {
        // Fallback - zkopíruj do schránky
        navigator.clipboard.writeText(text).then(() => {
            alert('Text zkopírován do schránky!');
        });
    }
}

// ==================== EVENT LISTENERS ====================
function initEventListeners() {
    // Zavření modálů kliknutím mimo
    document.addEventListener('click', function(event) {
        const helpModal = document.getElementById('helpModal');
        if (event.target === helpModal) {
            toggleHelp();
        }
    });
    
    // ESC pro zavření modálů
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const helpModal = document.getElementById('helpModal');
            if (helpModal.classList.contains('active')) {
                toggleHelp();
            }
        }
    });
}

// ==================== UTILITY ====================
function confirmExit() {
    if (confirm('Opravdu chceš ukončit hru? Postup nebude uložen.')) {
        window.location.href = 'dashboard.php';
    }
}

function showNotification(message) {
    // Jednoduchá notifikace (můžete vylepšit)
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 80px;
        left: 50%;
        transform: translateX(-50%);
        background: #333;
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        z-index: 3000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// První úkol je vždy odemčen
GAME_DATA.waypoints[0].unlocked = true;