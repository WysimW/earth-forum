/**
 * Script de gestion des messages non lus dans la messagerie
 * - Met à jour périodiquement le compteur de messages non lus
 * - Indépendant du système de posts non lus
 */

class MessagingUnreadManager {
    constructor() {
        this.countUrl = '/messaging/unread/count';
        this.updateInterval = 30000; // 30 secondes
        this.sidebarBadgeElement = document.querySelector('.sidebar .messaging-badge');
        this.navbarBadgeElement = document.querySelector('.navbar .messaging-badge');
        this.init();
    }

    init() {
        // Si nous sommes sur une page avec un utilisateur connecté
        if (document.body.classList.contains('user-logged-in')) {
            this.startAutoRefresh();
        }
    }

    /**
     * Démarre le rafraîchissement automatique du compteur
     */
    startAutoRefresh() {
        // Première mise à jour immédiate
        this.updateUnreadCount();
        
        // Mise à jour périodique
        setInterval(() => {
            this.updateUnreadCount();
        }, this.updateInterval);
    }

    /**
     * Met à jour le compteur de messages non lus via une requête AJAX
     */
    updateUnreadCount() {
        fetch(this.countUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            this.updateBadges(data.total);
        })
        .catch(error => console.error('Erreur lors de la récupération du nombre de messages non lus de la messagerie:', error));
    }

    /**
     * Met à jour les badges affichant le nombre de messages non lus
     */
    updateBadges(count) {
        // Mise à jour du badge dans la sidebar
        if (this.sidebarBadgeElement) {
            if (count > 0) {
                this.sidebarBadgeElement.textContent = count;
                this.sidebarBadgeElement.style.display = 'inline-block';
            } else {
                this.sidebarBadgeElement.style.display = 'none';
            }
        }
        
        // Mise à jour du badge dans la navbar (si présent)
        if (this.navbarBadgeElement) {
            if (count > 0) {
                this.navbarBadgeElement.textContent = count;
                this.navbarBadgeElement.style.display = 'inline-block';
            } else {
                this.navbarBadgeElement.style.display = 'none';
            }
        }
    }
}

// Initialisation du gestionnaire de messages non lus de la messagerie
document.addEventListener('DOMContentLoaded', function() {
    new MessagingUnreadManager();
}); 