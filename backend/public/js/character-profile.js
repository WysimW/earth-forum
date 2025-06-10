/**
 * Gestion simple et efficace du profil de personnage
 */

// Version simplifiée qui fonctionne à coup sûr
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initialisation simple des onglets...');
    
    const tabButtons = document.querySelectorAll('.character-tabs__tab');
    const tabPanels = document.querySelectorAll('.character-tabs__panel');
    
    console.log(`📋 ${tabButtons.length} onglets et ${tabPanels.length} panneaux trouvés`);
    
    if (tabButtons.length === 0 || tabPanels.length === 0) {
        console.warn('⚠️ Aucun onglet trouvé');
        return;
    }
    
    // Configuration simple des onglets
    tabButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetTabId = this.getAttribute('data-tab');
            console.log(`🖱️ Clic sur onglet: ${targetTabId}`);
            
            // Désactiver tous les onglets et panneaux
            tabButtons.forEach(function(btn) {
                btn.classList.remove('active');
            });
            
            tabPanels.forEach(function(panel) {
                panel.classList.remove('active');
            });
            
            // Activer l'onglet cliqué
            this.classList.add('active');
            
            // Activer le panneau correspondant
            const targetPanel = document.getElementById(targetTabId);
            if (targetPanel) {
                targetPanel.classList.add('active');
                console.log(`✅ Onglet ${targetTabId} activé`);
            } else {
                console.error(`❌ Panneau ${targetTabId} non trouvé`);
            }
        });
    });
    
    // S'assurer qu'un onglet est actif au démarrage
    const activeTab = document.querySelector('.character-tabs__tab.active');
    const activePanel = document.querySelector('.character-tabs__panel.active');
    
    if (!activeTab && tabButtons.length > 0) {
        console.log('🔧 Activation du premier onglet par défaut');
        tabButtons[0].classList.add('active');
        
        const firstTabId = tabButtons[0].getAttribute('data-tab');
        const firstPanel = document.getElementById(firstTabId);
        if (firstPanel) {
            firstPanel.classList.add('active');
        }
    }
    
    console.log('✅ Onglets configurés avec succès');
    
    // Gestion des labels de statut
    const statusLabels = document.querySelectorAll('.status-label');
    statusLabels.forEach(function(label) {
        label.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        label.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Fonctions globales
    window.CharacterProfileUtils = {
        switchTab: function(tabId) {
            const button = document.querySelector(`[data-tab="${tabId}"]`);
            if (button) {
                button.click();
            }
        },
        
        testTabs: function() {
            console.log('🧪 Test des onglets:');
            tabButtons.forEach(function(tab, index) {
                console.log(`  Onglet ${index + 1}: ${tab.getAttribute('data-tab')} - Actif: ${tab.classList.contains('active')}`);
            });
            
            tabPanels.forEach(function(panel, index) {
                console.log(`  Panneau ${index + 1}: ${panel.id} - Visible: ${panel.classList.contains('active')}`);
            });
        }
    };
    
    // Test automatique
    setTimeout(function() {
        console.log('🔍 État des onglets après initialisation:');
        if (window.CharacterProfileUtils) {
            window.CharacterProfileUtils.testTabs();
        }
    }, 500);
}); 