/**
 * Script de gestion des messages non lus
 * - Met à jour périodiquement le compteur de messages non lus
 * - Gère les préférences d'affichage des messages
 */

class UnreadMessagesManager {
    constructor() {
        this.countUrl = '/unread-messages/count';
        this.updateInterval = 30000; // 30 secondes
        this.navbarCounterElement = document.querySelector('.navbar .badge');
        this.sidebarCounterElement = document.querySelector('.sidebar .badge');
        this.forumsWithUnreadMessages = new Set(); // Pour stocker les IDs des forums avec messages non lus
        this.threadsWithUnreadMessages = new Set(); // Pour stocker les IDs des threads avec messages non lus
        this.init();
    }

    init() {
        this.startAutoRefresh();
        this.checkForumUnreadIndicators();
        this.checkThreadUnreadIndicators();
        this.setupPreferenceListeners();
    }

    /**
     * Configure les écouteurs d'événements pour les préférences utilisateur
     */
    setupPreferenceListeners() {
        // Si le sélecteur de préférence est présent (page des messages non lus)
        const filterCheckbox = document.getElementById('filterNonParticipatingMessages');
        if (filterCheckbox) {
            filterCheckbox.addEventListener('change', () => {
                // Après le changement de préférence, mettre à jour les indicateurs
                setTimeout(() => {
                    this.checkForumUnreadIndicators();
                    this.checkThreadUnreadIndicators();
                }, 500);
            });
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
            this.updateCounterElements(data.total);
        })
        .catch(error => console.error('Erreur lors de la récupération du nombre de messages non lus:', error));
    }

    /**
     * Met à jour les éléments HTML affichant le nombre de messages non lus
     */
    updateCounterElements(count) {
        // Mise à jour du compteur dans la navbar
        if (this.navbarCounterElement) {
            if (count > 0) {
                this.navbarCounterElement.textContent = count;
                this.navbarCounterElement.style.display = 'inline-block';
            } else {
                this.navbarCounterElement.style.display = 'none';
            }
        }
        
        // Mise à jour du compteur dans la sidebar
        if (this.sidebarCounterElement) {
            if (count > 0) {
                this.sidebarCounterElement.textContent = count;
                this.sidebarCounterElement.style.display = 'inline-block';
            } else {
                this.sidebarCounterElement.style.display = 'none';
            }
        }
    }

    /**
     * Vérifie les messages non lus pour chaque forum
     */
    checkForumUnreadIndicators() {
        const forumCards = document.querySelectorAll('.forum-card');
        
        // Si aucune carte de forum n'est présente ou si l'utilisateur n'est pas connecté, ne pas continuer
        if (forumCards.length === 0 || !document.body.classList.contains('user-logged-in')) {
            return;
        }
        
        // Réinitialiser la liste des forums avec messages non lus
        this.forumsWithUnreadMessages.clear();
        
        // Première passe : vérifier chaque forum individuellement
        const promises = [];
        
        forumCards.forEach(card => {
            const forumId = card.getAttribute('data-forum-id');
            if (!forumId) return;
            
            // Faire une requête AJAX pour vérifier les messages non lus
            const promise = fetch(`/unread-messages/check-forum/${forumId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.hasUnread) {
                    // Ajouter ce forum à la liste des forums avec messages non lus
                    this.forumsWithUnreadMessages.add(forumId);
                }
                return { forumId, data };
            })
            .catch(error => {
                console.error('Erreur lors de la vérification des messages non lus:', error);
                return null;
            });
            
            promises.push(promise);
        });
        
        // Attendre que toutes les requêtes soient terminées
        Promise.all(promises).then(results => {
            // Deuxième passe : propager l'information aux forums parents
            forumCards.forEach(card => {
                const forumId = card.getAttribute('data-forum-id');
                const parentId = card.getAttribute('data-parent-id');
                
                // Si ce forum a un parent et a des messages non lus, ajouter le parent à la liste
                if (parentId && this.forumsWithUnreadMessages.has(forumId)) {
                    this.forumsWithUnreadMessages.add(parentId);
                }
            });
            
            // Troisième passe : mettre à jour l'affichage des indicateurs
            forumCards.forEach(card => {
                const forumId = card.getAttribute('data-forum-id');
                const unreadIndicator = card.querySelector('.unread-indicator');
                const clockIcon = card.querySelector('.last-clock-icon');
                const countElement = unreadIndicator ? unreadIndicator.querySelector('.unread-count') : null;
                
                if (!unreadIndicator) return;
                
                // Vérifier si ce forum ou l'un de ses sous-forums a des messages non lus
                if (this.forumsWithUnreadMessages.has(forumId)) {
                    // Afficher l'indicateur de messages non lus
                    unreadIndicator.style.display = 'inline-flex';
                    
                    // Masquer l'icône d'horloge si elle existe
                    if (clockIcon) {
                        clockIcon.style.display = 'none';
                    }
                    
                    // Chercher le résultat correspondant pour obtenir le compteur
                    const result = results.find(r => r && r.forumId === forumId);
                    if (result && countElement) {
                        // Toujours afficher le compteur
                        const count = result.data.count || 0;
                        countElement.textContent = count > 99 ? '99+' : count;
                    }
                } else {
                    // Masquer l'indicateur de messages non lus
                    unreadIndicator.style.display = 'none';
                    
                    // Afficher l'icône d'horloge si elle existe
                    if (clockIcon) {
                        clockIcon.style.display = 'inline-flex';
                    }
                }
            });
        });
        
        // Vérifier périodiquement les messages non lus (toutes les 2 minutes)
        setTimeout(() => this.checkForumUnreadIndicators(), 120000);
    }

    /**
     * Vérifie les messages non lus pour chaque thread
     */
    checkThreadUnreadIndicators() {
        // Mise à jour du sélecteur pour cibler thread-card au lieu de thread-row
        const threadCards = document.querySelectorAll('.thread-card');
        
        // Si aucune carte de thread n'est présente ou si l'utilisateur n'est pas connecté, ne pas continuer
        if (threadCards.length === 0 || !document.body.classList.contains('user-logged-in')) {
            return;
        }
        
        // Réinitialiser la liste des threads avec messages non lus
        this.threadsWithUnreadMessages.clear();
        
        // Vérifier chaque thread individuellement
        threadCards.forEach(card => {
            const threadId = card.getAttribute('data-thread-id');
            if (!threadId) return;
            
            const indicator = card.querySelector('.thread-unread-indicator');
            const clockIcon = card.querySelector('.thread-clock-icon');
            const countElement = indicator ? indicator.querySelector('.unread-count') : null;
            
            if (!indicator || !clockIcon) return;
            
            // S'assurer que l'indicateur est masqué au départ
            indicator.classList.add('hidden');
            indicator.style.display = 'none';
            
            // S'assurer que l'icône d'horloge est visible au départ
            clockIcon.style.display = 'inline-flex';
            
            // Faire une requête AJAX pour vérifier les messages non lus
            fetch(`/unread-messages/check-thread/${threadId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Vérifier si le thread a des messages non lus ET que le compteur est > 0
                if (data.hasUnread && data.count > 0) {
                    // Ajouter ce thread à la liste des threads avec messages non lus
                    this.threadsWithUnreadMessages.add(threadId);
                    
                    // Mettre à jour le compteur
                    if (countElement) {
                        countElement.textContent = data.count > 99 ? '99+' : data.count;
                    }
                    
                    // Afficher l'indicateur et masquer l'icône d'horloge
                    indicator.classList.remove('hidden');
                    indicator.style.display = 'inline-flex';
                    clockIcon.style.display = 'none';
                } else {
                    // Masquer l'indicateur et afficher l'icône d'horloge
                    indicator.classList.add('hidden');
                    indicator.style.display = 'none';
                    clockIcon.style.display = 'inline-flex';
                }
            })
            .catch(error => {
                console.error('Erreur lors de la vérification des messages non lus:', error);
                // En cas d'erreur, afficher l'icône d'horloge par défaut
                indicator.classList.add('hidden');
                indicator.style.display = 'none';
                clockIcon.style.display = 'inline-flex';
            });
        });
        
        // Vérifier périodiquement les messages non lus (toutes les 2 minutes)
        setTimeout(() => this.checkThreadUnreadIndicators(), 120000);
    }
}

// Initialisation du gestionnaire de messages non lus
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter la classe user-logged-in au body si l'utilisateur est connecté
    const userMenuDropdown = document.getElementById('userDropdown');
    if (userMenuDropdown) {
        document.body.classList.add('user-logged-in');
    }
    
    new UnreadMessagesManager();
}); 