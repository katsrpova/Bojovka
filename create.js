// Difficulty selection handling
document.querySelectorAll('.difficulty-option').forEach(option => {
    option.addEventListener('click', function() {
        // Odstraň selected třídu ze všech opcí
        document.querySelectorAll('.difficulty-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        // Přidej selected třídu na kliknutou opci
        this.classList.add('selected');
        // Zaškrtni příslušné radio tlačítko
        this.querySelector('input[type="radio"]').checked = true;
    });
});

// Přednastav vybranou obtížnost při načtení stránky
document.addEventListener('DOMContentLoaded', function() {
    const selectedRadio = document.querySelector('input[name="difficulty"]:checked');
    if (selectedRadio) {
        selectedRadio.closest('.difficulty-option').classList.add('selected');
    }
});

// Form validation a submit handling
document.getElementById('createGameForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Získej hodnoty z formuláře
    const gameName = document.querySelector('input[name="game_name"]').value.trim();
    const gameDesc = document.querySelector('textarea[name="game_description"]').value.trim();
    const startLocation = document.querySelector('input[name="start_location"]').value.trim();
    const difficulty = document.querySelector('input[name="difficulty"]:checked');
    
    // Validace povinných polí
    if (!gameName) {
        alert('Prosím zadejte název hry');
        document.querySelector('input[name="game_name"]').focus();
        return;
    }
    
    if (!gameDesc) {
        alert('Prosím zadejte popis hry');
        document.querySelector('textarea[name="game_description"]').focus();
        return;
    }
    
    if (!startLocation) {
        alert('Prosím zadejte startovní lokaci');
        document.querySelector('input[name="start_location"]').focus();
        return;
    }
    
    if (!difficulty) {
        alert('Prosím vyberte obtížnost');
        return;
    }
    
    // Kontrola délky názvu
    if (gameName.length < 3) {
        alert('Název hry musí mít alespoň 3 znaky');
        document.querySelector('input[name="game_name"]').focus();
        return;
    }
    
    // Kontrola délky popisu
    if (gameDesc.length < 20) {
        alert('Popis hry musí mít alespoň 20 znaků');
        document.querySelector('textarea[name="game_description"]').focus();
        return;
    }
    
    // Pokud vše prošlo validací, simuluj úspěch (později odkomentuj this.submit())
    console.log('Form data:', {
        gameName,
        gameDesc,
        startLocation,
        difficulty: difficulty.value,
        estimatedTime: document.querySelector('select[name="estimated_time"]').value,
        checkpointCount: document.querySelector('input[name="checkpoint_count"]').value
    });
    
    alert('Hra byla úspěšně vytvořena! (Demo režim)\n\nPro plnou funkčnost vytvořte soubor create_game_handler.php');
    
    // Pro produkční použití odkomentujte:
    // this.submit();
});

// Optional: Živá validace při psaní
document.querySelector('input[name="game_name"]').addEventListener('input', function() {
    const length = this.value.trim().length;
    const helper = this.parentElement.querySelector('.form-helper');
    
    if (!helper) {
        const newHelper = document.createElement('div');
        newHelper.className = 'form-helper';
        this.parentElement.appendChild(newHelper);
    }
    
    if (length > 0 && length < 3) {
        this.style.borderColor = '#dc3545';
    } else if (length >= 3) {
        this.style.borderColor = '#28a745';
    } else {
        this.style.borderColor = '#e0e0e0';
    }
});

document.querySelector('textarea[name="game_description"]').addEventListener('input', function() {
    const length = this.value.trim().length;
    const helper = this.parentElement.querySelector('.form-helper');
    
    if (helper) {
        if (length > 0 && length < 20) {
            helper.textContent = `Ještě ${20 - length} znaků do minima`;
            helper.style.color = '#dc3545';
            this.style.borderColor = '#dc3545';
        } else if (length >= 20) {
            helper.textContent = 'Napište zajímavý popis, který přiláká další hráče';
            helper.style.color = '#666';
            this.style.borderColor = '#28a745';
        } else {
            helper.textContent = 'Napište zajímavý popis, který přiláká další hráče';
            helper.style.color = '#666';
            this.style.borderColor = '#e0e0e0';
        }
    }
});

// Optional: Simulace mapy při zadání lokace
document.querySelector('input[name="start_location"]').addEventListener('blur', function() {
    const location = this.value.trim();
    const mapPreview = document.querySelector('.map-preview');
    
    if (location) {
        mapPreview.innerHTML = `
            <i class="fas fa-map-marked-alt"></i>
            <p><strong>${location}</strong></p>
            <p style="font-size: 12px; margin-top: 5px;">Lokace zadána (integrace s mapou bude přidána)</p>
        `;
        mapPreview.style.borderColor = '#28a745';
    } else {
        mapPreview.innerHTML = `
            <i class="fas fa-map"></i>
            <p>Náhled mapy se zobrazí po zadání lokace</p>
        `;
        mapPreview.style.borderColor = '#ddd';
    }
});