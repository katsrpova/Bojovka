// ==================== GLOBÁLNÍ PROMĚNNÉ ====================
let map;
let markers = [];
let waypoints = [];
let waypointCounter = 0;

// ==================== INICIALIZACE ====================
document.addEventListener('DOMContentLoaded', function() {
    initMap();
    initEventListeners();
});

// ==================== MAPA ====================
function initMap() {
    try {
        console.log('✓ Initializing Leaflet map...');
        
        // Vytvoř mapu - Praha jako výchozí centrum
        map = L.map('map').setView([50.0755, 14.4378], 13);

        // Získej API klíč z HTML data atributu
        const apiKey = document.getElementById('map').dataset.apikey;

        // Přidej Mapy.cz tile layer pomocí REST API
        L.tileLayer('https://api.mapy.cz/v1/maptiles/basic/256/{z}/{x}/{y}?apikey=' + apiKey, {
            minZoom: 0,
            maxZoom: 19,
            attribution: '<a href="https://api.mapy.cz/copyright" target="_blank">&copy; Seznam.cz a.s. a další</a>',
        }).addTo(map);

        // Logo Mapy.cz (povinné pro použití API)
        const LogoControl = L.Control.extend({
            options: {
                position: 'bottomleft',
            },

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

        console.log('✓ Map created successfully');

        // Event listener pro kliknutí na mapu
        map.on('click', function(e) {
            addWaypoint(e.latlng);
        });

        console.log('✓ Map is ready! Click to add waypoints.');

        // Získej polohu uživatele
        getUserLocation();

    } catch (error) {
        console.error('Error initializing map:', error);
        document.getElementById('map').innerHTML = 
            '<div style="padding: 40px; text-align: center; color: #dc3545;">' +
            'Chyba při načítání mapy: ' + error.message + 
            '<br><small>Zkuste obnovit stránku</small></div>';
    }
}

function getUserLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            
            map.setView([lat, lon], 15);
            console.log('✓ User location:', lat, lon);
        }, function(error) {
            console.log('Geolocation error:', error.message);
        });
    }
}

// ==================== WAYPOINTS ====================
function addWaypoint(latlng) {
    waypointCounter++;
    
    const waypoint = {
        id: waypointCounter,
        lat: latlng.lat,
        lng: latlng.lng,
        name: `Úkol ${waypointCounter}`,
        description: '',
        type: 'checkpoint'
    };
    
    waypoints.push(waypoint);

    // Vytvoř custom marker s číslem
    const icon = L.divIcon({
        className: 'custom-marker',
        html: '<div class="marker-pin"><span>' + waypointCounter + '</span></div>',
        iconSize: [30, 42],
        iconAnchor: [15, 42]
    });

    const marker = L.marker(latlng, { icon: icon }).addTo(map);
    marker.bindPopup(`<strong>${waypoint.name}</strong>`);
    markers.push(marker);
    
    console.log('✓ Waypoint added:', waypoint.name);
    
    updateTasksList();
    updateWaypointsData();
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
    const index = waypoints.findIndex(w => w.id === id);
    if (index === -1) return;
    
    // Odstraň waypoint
    waypoints.splice(index, 1);
    
    // Odstraň marker z mapy
    if (markers[index]) {
        map.removeLayer(markers[index]);
        markers.splice(index, 1);
    }
    
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

function updateWaypointsData() {
    document.getElementById('waypointsData').value = JSON.stringify(waypoints);
    console.log('Waypoints updated:', waypoints.length);
}

// ==================== EVENT LISTENERS ====================
function initEventListeners() {
    // Tlačítko Moje poloha
    document.getElementById('myLocationBtn').addEventListener('click', function() {
        getUserLocation();
    });

    // Tlačítko Smazat všechny značky
    document.getElementById('clearMarkersBtn').addEventListener('click', function() {
        if (confirm('Opravdu chceš smazat všechny úkoly?')) {
            // Odstraň všechny markery z mapy
            markers.forEach(marker => {
                map.removeLayer(marker);
            });
            
            waypoints = [];
            markers = [];
            waypointCounter = 0;
            
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

    // Form submit validace
    document.getElementById('createGameForm').addEventListener('submit', function(e) {
        const gameName = document.getElementById('gameName').value.trim();
        const gameDescription = document.getElementById('gameDescription').value.trim();
        
        if (!gameName) {
            e.preventDefault();
            alert('Prosím zadejte název hry!');
            return;
        }
        
        if (!gameDescription) {
            e.preventDefault();
            alert('Prosím zadejte popis hry!');
            return;
        }
        
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
        
        // Kontrola, zda všechny úkoly mají popis
        const emptyTasks = waypoints.filter(wp => !wp.description.trim());
        if (emptyTasks.length > 0) {
            if (!confirm(`${emptyTasks.length} úkol(y) nemají popis. Chceš pokračovat?`)) {
                e.preventDefault();
                return;
            }
        }
        
        console.log('Submitting game with', waypoints.length, 'waypoints');
    });
}