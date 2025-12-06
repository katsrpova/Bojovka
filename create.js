// ==================== CREATE PAGE FUNCTIONALITY ====================

let waypoints = [];
let addMode = false;
let map = null; // API MAPY.CZ - instance mapy

// ==================== MAP INITIALIZATION ====================
// API MAPY.CZ - zde se inicializuje mapa
function initMap() {
    // TODO: Inicializovat Mapy.cz
    console.log('Map initialization - waiting for Mapy.cz API');
    
    // Příklad, jak by to mohlo vypadat:
    /*
    Loader.async = true;
    Loader.load(null, null, () => {
        const center = SMap.Coords.fromWGS84(14.4378, 50.0755); // Praha
        map = new SMap(document.getElementById('map'), center, 13);
        map.addDefaultLayer(SMap.DEF_BASE).enable();
        map.addDefaultControls();
        
        // Click event pro přidávání waypointů
        map.getSignals().addListener(this, 'map-click', handleMapClick);
    });
    */
}

// ==================== WAYPOINT MANAGEMENT ====================

function toggleAddMode() {
    addMode = !addMode;
    const btn = document.querySelector('.tool-btn');
    if (addMode) {
        btn.classList.add('active');
    } else {
        btn.classList.remove('active');
    }
}

function handleMapClick(e) {
    // API MAPY.CZ - zpracování kliknutí na mapu
    if (!addMode) return;
    
    // TODO: Získat souřadnice z události
    // const coords = e.data.coords;
    
    addWaypoint({
        lat: 50.0755, // Placeholder
        lng: 14.4378, // Placeholder
        name: `Waypoint ${waypoints.length + 1}`
    });
}

function addWaypoint(data) {
    const waypoint = {
        id: Date.now(),
        ...data,
        description: '',
        type: 'checkpoint'
    };
    
    waypoints.push(waypoint);
    updateWaypointsList();
    updateMapMarkers();
    updateStats();
}

function removeWaypoint(id) {
    waypoints = waypoints.filter(w => w.id !== id);
    updateWaypointsList();
    updateMapMarkers();
    updateStats();
}

function updateWaypointsList() {
    const list = document.getElementById('waypointsList');
    const count = document.getElementById('waypointCount');
    
    count.textContent = waypoints.length;
    
    if (waypoints.length === 0) {
        list.innerHTML = '<p class="empty-message">Click on map to add waypoints</p>';
        return;
    }
    
    list.innerHTML = waypoints.map((wp, index) => `
        <div class="waypoint-item">
            <div class="waypoint-header">
                <span class="waypoint-number">${index + 1}</span>
                <input type="text" value="${wp.name}" class="waypoint-name" 
                       onchange="updateWaypointName(${wp.id}, this.value)">
                <button class="btn-remove" onclick="removeWaypoint(${wp.id})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function updateWaypointName(id, name) {
    const waypoint = waypoints.find(w => w.id === id);
    if (waypoint) {
        waypoint.name = name;
    }
}

// ==================== MAP FUNCTIONS ====================

function centerMap() {
    // API MAPY.CZ - vycentrovat mapu
    if (map && waypoints.length > 0) {
        // TODO: Vycentrovat na waypoints
        console.log('Centering map');
    }
}

function zoomIn() {
    // API MAPY.CZ - přiblížit
    if (map) {
        console.log('Zoom in');
        // map.setZoom(map.getZoom() + 1);
    }
}

function zoomOut() {
    // API MAPY.CZ - oddálit
    if (map) {
        console.log('Zoom out');
        // map.setZoom(map.getZoom() - 1);
    }
}

function updateMapMarkers() {
    // API MAPY.CZ - aktualizovat značky na mapě
    if (!map) return;
    
    // TODO: Vymazat staré značky
    // TODO: Přidat nové značky z waypoints pole
    console.log('Updating markers:', waypoints);
}

// ==================== STATS ====================

function updateStats() {
    // Vypočítat celkovou vzdálenost
    let totalDistance = 0;
    
    for (let i = 0; i < waypoints.length - 1; i++) {
        // TODO: Vypočítat vzdálenost mezi waypoints[i] a waypoints[i+1]
        totalDistance += 0.5; // Placeholder
    }
    
    const totalTime = Math.round(totalDistance * 12); // Odhad: 12 min/km
    
    document.getElementById('totalDistance').textContent = `${totalDistance.toFixed(1)} km`;
    document.getElementById('totalTime').textContent = `${totalTime} min`;
}

// ==================== SAVE & CANCEL ====================

function saveAdventure() {
    const gameName = document.getElementById('gameName').value;
    const gameDescription = document.getElementById('gameDescription').value;
    const gameDifficulty = document.getElementById('gameDifficulty').value;
    
    if (!gameName) {
        alert('Please enter a game name');
        return;
    }
    
    if (waypoints.length < 2) {
        alert('Please add at least 2 waypoints');
        return;
    }
    
    const adventure = {
        name: gameName,
        description: gameDescription,
        difficulty: gameDifficulty,
        waypoints: waypoints
    };
    
    console.log('Saving adventure:', adventure);
    
    // TODO: Odeslat na server
    // fetch('save_adventure.php', { ... })
    
    alert('Adventure saved! (Demo mode)');
    window.location.href = 'dashboard.php';
}

function cancelCreate() {
    if (waypoints.length > 0) {
        if (!confirm('Are you sure? All changes will be lost.')) {
            return;
        }
    }
    window.location.href = 'dashboard.php';
}

// ==================== INITIALIZATION ====================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Create page loaded');
    initMap();
});