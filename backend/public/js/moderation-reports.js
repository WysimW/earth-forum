/**
 * Script de gestion des signalements de modération
 * - Met à jour périodiquement le compteur de signalements en attente
 * - Affiche une notification visuelle pour les modérateurs
 */

class ModerationReportsManager {
    constructor() {
        this.countUrl = '/messaging/moderation/reports/count';
        this.updateInterval = 60000; // 60 secondes
        this.reportsBadge = document.getElementById('reports-badge');
        this.init();
    }

    init() {
        if (!this.reportsBadge) return;
        
        // Vérifier si l'utilisateur a un rôle de modérateur
        if (!document.body.classList.contains('user-logged-in')) return;
        
        this.startAutoRefresh();
    }

    /**
     * Démarre le rafraîchissement automatique du compteur
     */
    startAutoRefresh() {
        // Première mise à jour immédiate
        this.updateReportsCount();
        
        // Mise à jour périodique
        setInterval(() => {
            this.updateReportsCount();
        }, this.updateInterval);
    }

    /**
     * Met à jour le compteur de signalements via une requête AJAX
     */
    updateReportsCount() {
        fetch(this.countUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            this.updateBadge(data.count);
        })
        .catch(error => console.error('Erreur lors de la récupération du nombre de signalements:', error));
    }

    /**
     * Met à jour l'élément HTML affichant le nombre de signalements
     */
    updateBadge(count) {
        if (!this.reportsBadge) return;
        
        if (count > 0) {
            this.reportsBadge.textContent = count;
            this.reportsBadge.style.display = 'inline-block';
            
            // Ajout d'une animation subtile pour attirer l'attention
            this.reportsBadge.classList.add('pulse');
            setTimeout(() => {
                this.reportsBadge.classList.remove('pulse');
            }, 1000);
        } else {
            this.reportsBadge.style.display = 'none';
        }
    }
}

// Initialisation du gestionnaire de signalements
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si le badge de modération existe (indiquant que l'utilisateur est modérateur)
    const reportsBadge = document.getElementById('reports-badge');
    if (reportsBadge) {
        new ModerationReportsManager();
    }
}); 