// ==================== DASHBOARD FUNCTIONALITY ====================

// Start Adventure
function startAdventure(adventureId) {
    console.log('Starting adventure:', adventureId);
    // TODO: Přesměrovat na stránku hry
    window.location.href = `play.php?id=${adventureId}`;
}

// Filter Adventures (pro budoucnost)
function filterAdventures(difficulty) {
    const cards = document.querySelectorAll('.adventure-card');
    
    cards.forEach(card => {
        if (difficulty === 'all') {
            card.style.display = 'block';
        } else {
            const badge = card.querySelector('.difficulty-badge');
            if (badge && badge.classList.contains(difficulty)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        }
    });
}

// Search Adventures (pro budoucnost)
function searchAdventures(query) {
    const cards = document.querySelectorAll('.adventure-card');
    query = query.toLowerCase();
    
    cards.forEach(card => {
        const title = card.querySelector('h3').textContent.toLowerCase();
        const description = card.querySelector('.description').textContent.toLowerCase();
        
        if (title.includes(query) || description.includes(query)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Smooth scroll to top
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard loaded');
    
    // Animate cards on load
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