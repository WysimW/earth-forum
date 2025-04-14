document.addEventListener('DOMContentLoaded', function() {
    // Récupération des éléments
    const characterTabs = document.getElementById('characterTabs');
    const characterTabContent = document.getElementById('characterTabContent');
    
    if (characterTabs && characterTabContent) {
        // Gestionnaire d'événements pour les onglets
        characterTabs.addEventListener('click', function(e) {
            const tabButton = e.target.closest('.character-tab-link');
            if (!tabButton) return;
            
            e.preventDefault();
            
            // Récupération des IDs
            const targetId = tabButton.getAttribute('data-bs-target');
            const tabId = tabButton.getAttribute('id');
            
            // Mise à jour des onglets actifs
            characterTabs.querySelectorAll('.character-tab-link').forEach(link => {
                link.classList.remove('active');
            });
            tabButton.classList.add('active');
            
            // Mise à jour du contenu actif
            characterTabContent.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });
            const activePane = characterTabContent.querySelector(targetId);
            if (activePane) {
                activePane.classList.add('show', 'active');
            }
        });
    }
}); 