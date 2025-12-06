// ==================== DASHBOARD FUNCTIONALITY ====================

// Start Adventure
function startAdventure(adventureId) {
    console.log('Starting adventure:', adventureId);
    // TODO: Přesměrovat na stránku hry
    window.location.href = `play.php?id=${adventureId}`;
}

// ==================== SEARCH FUNCTIONALITY ====================

// Search Adventures
function searchAdventures() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.adventure-card');
    
    cards.forEach(card => {
        const title = card.querySelector('h3').textContent.toLowerCase();
        const author = card.querySelector('.author').textContent.toLowerCase();
        
        if (title.includes(query) || author.includes(query)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// ==================== JOIN BY CODE MODAL ====================

// Open Join Modal
function openJoinModal() {
    const modal = document.getElementById('joinModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Close Join Modal
function closeJoinModal() {
    const modal = document.getElementById('joinModal');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
    
    // Reset form
    document.getElementById('gameCode').value = '';
}

// Switch Join Tab
function switchJoinTab(tab) {
    // Remove active from all tabs
    document.querySelectorAll('.join-tab').forEach(t => {
        t.classList.remove('active');
    });
    document.querySelectorAll('.join-content').forEach(c => {
        c.classList.remove('active');
    });
    
    // Add active to selected tab
    if (tab === 'code') {
        document.querySelector('.join-tab:first-child').classList.add('active');
        document.getElementById('codeContent').classList.add('active');
    } else {
        document.querySelector('.join-tab:last-child').classList.add('active');
        document.getElementById('qrContent').classList.add('active');
    }
}

// Join by Code
function joinByCode() {
    const code = document.getElementById('gameCode').value;
    
    if (code.length !== 6) {
        alert('Please enter a 6-digit code');
        return;
    }
    
    // TODO: Ověřit kód na serveru a přesměrovat na hru
    console.log('Joining adventure with code:', code);
    window.location.href = `play.php?code=${code}`;
}

// Close modal on outside click
document.addEventListener('click', function(event) {
    const modal = document.getElementById('joinModal');
    if (event.target === modal) {
        closeJoinModal();
    }
});

// Close modal on ESC key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeJoinModal();
    }
});

// Auto-focus code input when modal opens
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('joinModal');
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                if (modal.classList.contains('active')) {
                    setTimeout(() => {
                        document.getElementById('gameCode').focus();
                    }, 100);
                }
            }
        });
    });
    
    observer.observe(modal, { attributes: true });
});

// ==================== ANIMATIONS ====================

// Animate cards on load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard loaded');
    
    const cards = document.querySelectorAll('.adventure-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});