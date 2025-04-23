/**
 * Script de gestion des messages non lus dans la messagerie
 * - Met à jour périodiquement le compteur de messages non lus
 * - S'intègre avec le système de notification en temps réel
 */

class MessagingUnreadManager {
    constructor() {
        this.countUrl = '/messaging/unread/count';
        this.updateInterval = 10000; // 10 secondes au lieu de 30
        this.sidebarBadgeElement = document.querySelector('.sidebar .messaging-badge');
        this.navbarBadgeElement = document.querySelector('.navbar .messaging-badge');
        this.conversationBadges = {};
        this.lastUpdateTime = 0;
        this.init();
    }

    init() {
        // Si nous sommes sur une page avec un utilisateur connecté
        if (document.body.classList.contains('user-logged-in')) {
            // Première mise à jour immédiate
            this.updateUnreadCount();
            
            // Mise à jour périodique
            setInterval(() => {
                this.updateUnreadCount();
            }, this.updateInterval);
            
            // Écouter les événements de focus de la fenêtre pour mettre à jour immédiatement
            // quand l'utilisateur revient sur l'onglet
            window.addEventListener('focus', () => {
                // Ne mettre à jour que si la dernière mise à jour date de plus de 2 secondes
                const now = Date.now();
                if (now - this.lastUpdateTime > 2000) {
                    this.updateUnreadCount();
                }
            });
            
            // Exposer l'instance globalement pour permettre aux autres scripts d'y accéder
            window.messagingUnreadManager = this;
            
            // Publier un événement pour signaler que le gestionnaire est prêt
            document.dispatchEvent(new CustomEvent('messaging-unread-manager-ready', { detail: this }));
        }
    }

    /**
     * Met à jour le compteur de messages non lus via une requête AJAX
     * @param {boolean} forceUpdate - Forcer la mise à jour même si récente
     * @returns {Promise} - Une promesse résolue avec les données des messages non lus
     */
    updateUnreadCount(forceUpdate = false) {
        const now = Date.now();
        if (!forceUpdate && now - this.lastUpdateTime < 2000) {
            return Promise.resolve(null);
        }
        
        this.lastUpdateTime = now;
        
        return fetch(this.countUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateBadges(data.total);
                
                // Mettre à jour les badges de conversation si les détails sont disponibles
                if (data.conversations) {
                    this.updateConversationBadges(data.conversations);
                }
                
                // Émettre un événement avec les données mises à jour
                document.dispatchEvent(new CustomEvent('messaging-unread-updated', { 
                    detail: { 
                        total: data.total,
                        conversations: data.conversations || {} 
                    }
                }));
                
                return data;
            }
            return null;
        })
        .catch(error => {
            console.error('Erreur lors de la récupération du nombre de messages non lus de la messagerie:', error);
            return null;
        });
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
    
    /**
     * Met à jour les badges de conversation individuels si présents dans la page
     */
    updateConversationBadges(conversations) {
        // Mettre à jour les badges si nous sommes sur la page des conversations
        const conversationItems = document.querySelectorAll('.conversation-item');
        if (!conversationItems.length) return;
        
        conversationItems.forEach(item => {
            const conversationId = item.dataset.id;
            if (!conversationId) return;
            
            const unreadCount = conversations[conversationId] || 0;
            const unreadBadge = item.querySelector('.conversation-unread');
            const badgeSpan = unreadBadge?.querySelector('.badge');
            
            if (unreadCount > 0) {
                if (badgeSpan) {
                    badgeSpan.textContent = unreadCount;
                    unreadBadge.classList.remove('d-none');
                } else if (unreadBadge) {
                    // Créer le badge s'il n'existe pas
                    const newBadge = document.createElement('span');
                    newBadge.className = 'badge rounded-pill bg-danger shadow-sm';
                    newBadge.textContent = unreadCount;
                    unreadBadge.appendChild(newBadge);
                    unreadBadge.classList.remove('d-none');
                }
                
                // Ajouter la classe has-unread
                item.classList.add('has-unread');
            } else {
                // Masquer le badge s'il n'y a pas de messages non lus
                if (unreadBadge) {
                    unreadBadge.classList.add('d-none');
                }
                
                // Retirer la classe has-unread
                item.classList.remove('has-unread');
            }
        });
    }
    
    /**
     * Marquer une conversation comme lue
     * @param {number} conversationId - ID de la conversation
     */
    markConversationAsRead(conversationId) {
        if (!conversationId) return;
        
        // Mettre à jour visuellement immédiatement sans attendre la prochaine requête
        const conversationItem = document.querySelector(`.conversation-item[data-id="${conversationId}"]`);
        if (conversationItem) {
            conversationItem.classList.remove('has-unread');
            const unreadBadge = conversationItem.querySelector('.conversation-unread');
            if (unreadBadge) {
                unreadBadge.classList.add('d-none');
            }
        }
        
        // Forcer une mise à jour complète
        this.updateUnreadCount(true);
    }
}

// Initialisation du gestionnaire de messages non lus de la messagerie
document.addEventListener('DOMContentLoaded', function() {
    new MessagingUnreadManager();
}); 