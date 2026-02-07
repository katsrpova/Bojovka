// ==================== GLOBÁLNÍ PROMĚNNÉ ====================
let map;
let markers = [];
let waypoints = [];
let waypointCounter = 0;
let userLocationMarker = null;
let userLocationCircle = null;
let routeLine = null;
let apiKey = null;

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
        apiKey = document.getElementById('map').dataset.apikey;

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
        // Sleduj polohu průběžně
        navigator.geolocation.watchPosition(function(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            const accuracy = position.coords.accuracy;
            
            // Při prvním získání pozice vycentruj mapu
            if (!userLocationMarker) {
                map.setView([lat, lon], 15);
                console.log('✓ User location:', lat, lon);
            }
            
            // Aktualizuj nebo vytvoř marker pro polohu uživatele
            if (userLocationMarker) {
                userLocationMarker.setLatLng([lat, lon]);
                userLocationCircle.setLatLng([lat, lon]);
                userLocationCircle.setRadius(accuracy);
            } else {
                // Vytvoř modrý marker pro aktuální polohu
                const userIcon = L.divIcon({
                    className: 'user-location-marker',
                    html: '<div class="user-location-dot"></div>',
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                });
                
                userLocationMarker = L.marker([lat, lon], { icon: userIcon }).addTo(map);
                userLocationMarker.bindPopup('<strong>Vaše poloha</strong>');
                
                // Kruh znázorňující přesnost
                userLocationCircle = L.circle([lat, lon], {
                    radius: accuracy,
                    color: '#4285F4',
                    fillColor: '#4285F4',
                    fillOpacity: 0.1,
                    weight: 1
                }).addTo(map);
            }
        }, function(error) {
            console.log('Geolocation error:', error.message);
        }, {
            enableHighAccuracy: true,
            timeout: 5000,
            maximumAge: 0
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
        // Smaž trasu z mapy
        if (routeLine) {
            map.removeLayer(routeLine);
            routeLine = null;
        }
        // Skryj statistiky trasy
        document.getElementById('routeStats').style.display = 'none';
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
    
    // Pokud je více než 1 waypoint, naplánuj trasu
    if (waypoints.length >= 2) {
        planRoute();
    }
}

function updateWaypointsData() {
    document.getElementById('waypointsData').value = JSON.stringify(waypoints);
    console.log('Waypoints updated:', waypoints.length);
}

// ==================== PLÁNOVÁNÍ TRASY ====================
async function planRoute() {
    if (waypoints.length < 2) {
        return;
    }
    
    try {
        // Připrav body pro API
        const start = `${waypoints[0].lng},${waypoints[0].lat}`;
        const end = `${waypoints[waypoints.length - 1].lng},${waypoints[waypoints.length - 1].lat}`;
        
        // Průjezdní body (pokud jsou)
        let waypointsParam = '';
        if (waypoints.length > 2) {
            const middlePoints = waypoints.slice(1, -1)
                .map(wp => `${wp.lng},${wp.lat}`)
                .join(';');
            waypointsParam = `&waypoints=${encodeURIComponent(middlePoints)}`;
        }
        
        // API endpoint pro plánování trasy (pěšky)
        const url = `https://api.mapy.cz/v1/routing/route?` +
            `apikey=${apiKey}` +
            `&start=${start}` +
            `&end=${end}` +
            `${waypointsParam}` +
            `&routeType=foot_fast` +
            `&format=geojson`;
        
        console.log('Planning route...');
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Zobraz trasu na mapě
        displayRoute(data);
        
        // Zobraz statistiky
        displayRouteStats(data);
        
    } catch (error) {
        console.error('Error planning route:', error);
    }
}

function displayRoute(routeData) {
    // Odstraň starou trasu
    if (routeLine) {
        map.removeLayer(routeLine);
    }
    
    // Vytvoř novou trasu z GeoJSON
    routeLine = L.geoJSON(routeData.geometry, {
        style: {
            color: '#4285F4',
            weight: 4,
            opacity: 0.7
        }
    }).addTo(map);
    
    // Přizpůsob zoom aby byla vidět celá trasa
    const bounds = routeLine.getBounds();
    map.fitBounds(bounds, { padding: [50, 50] });
    
    console.log('✓ Route displayed');
}

function displayRouteStats(routeData) {
    const length = routeData.length; // v metrech
    const duration = routeData.duration; // v sekundách
    
    // Převod na čitelný formát
    const lengthKm = (length / 1000).toFixed(2);
    const durationMin = Math.round(duration / 60);
    
    // Zobraz statistiky
    const statsDiv = document.getElementById('routeStats');
    statsDiv.innerHTML = `
        <div class="route-stat">
            <i class="fas fa-route"></i>
            <span>Délka trasy: <strong>${lengthKm} km</strong></span>
        </div>
        <div class="route-stat">
            <i class="fas fa-clock"></i>
            <span>Odhadovaný čas: <strong>${durationMin} min</strong> (pěšky)</span>
        </div>
        <div class="route-stat">
            <i class="fas fa-map-marker-alt"></i>
            <span>Počet waypointů: <strong>${waypoints.length}</strong></span>
        </div>
    `;
    statsDiv.style.display = 'flex';
    
    console.log(`✓ Route stats: ${lengthKm} km, ${durationMin} min`);
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