/**
 * Gestion de la navigation rapide pour les threads
 */

class ThreadQuickNav {
    constructor() {
        this.postsQuickNav = document.getElementById('posts-quick-nav');
        console.log('Quick nav element found:', this.postsQuickNav);
        console.log('Posts count:', document.querySelectorAll('.post-container').length);
        if (this.postsQuickNav) {
            console.log('Quick nav classes:', this.postsQuickNav.className);
            console.log('Quick nav style:', getComputedStyle(this.postsQuickNav).opacity);
        }
        this.init();
        this.initSidebarAccordions();
        console.log('ThreadQuickNav chargé');
    }

    init() {
        if (!this.postsQuickNav) return;

        // La navigation est maintenant toujours visible, pas besoin de gestion de scroll
        this.setupEventListeners();
    }

    initSidebarAccordions() {
        // Initialiser les accordéons de la sidebar
        const accordionToggles = document.querySelectorAll('.character-details-toggle, .user-details-toggle');
        
        accordionToggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleAccordion(toggle);
            });
        });
        
        console.log(`Accordéons initialisés: ${accordionToggles.length}`);
    }

    toggleAccordion(toggle) {
        const target = toggle.getAttribute('data-bs-target');
        const content = document.querySelector(target);
        
        if (!content) return;
        
        const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
        
        // Mettre à jour l'état
        toggle.setAttribute('aria-expanded', !isExpanded);
        
        // Animer l'accordéon
        if (isExpanded) {
            // Fermer
            content.style.height = content.scrollHeight + 'px';
            content.offsetHeight; // Force reflow
            content.style.height = '0';
            content.style.opacity = '0';
            
            setTimeout(() => {
                content.classList.remove('show');
                content.style.height = '';
                content.style.opacity = '';
            }, 300);
        } else {
            // Ouvrir
            content.classList.add('show');
            content.style.height = '0';
            content.style.opacity = '0';
            
            requestAnimationFrame(() => {
                content.style.height = content.scrollHeight + 'px';
                content.style.opacity = '1';
                
                setTimeout(() => {
                    content.style.height = '';
                }, 300);
            });
        }
    }

    setupEventListeners() {
        // Gestion des clics de navigation
        this.postsQuickNav.addEventListener('click', (e) => {
            e.preventDefault();
            const target = e.target.closest('.quick-nav-item');
            if (!target) return;

            this.handleNavigation(target);
        });
    }

    handleNavigation(target) {
        if (target.classList.contains('last-post')) {
            // Aller au dernier post
            this.goToLastPost();
        } else if (target.classList.contains('character-filter')) {
            // Déléguer au système de filtrage par personnage
            if (window.characterFilter) {
                window.characterFilter.toggleFilter();
            }
        } else if (target.classList.contains('character-nav-prev')) {
            // Navigation vers le personnage précédent
            if (window.characterFilter) {
                window.characterFilter.navigateCharacter('prev');
            }
        } else if (target.classList.contains('character-nav-next')) {
            // Navigation vers le personnage suivant
            if (window.characterFilter) {
                window.characterFilter.navigateCharacter('next');
            }
        } else {
            // Pour les autres liens (href standards)
            this.handleStandardNavigation(target);
        }
    }

    goToLastPost() {
        const lastPost = document.querySelector('.post-container:last-of-type');
        if (lastPost) {
            lastPost.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    handleStandardNavigation(target) {
        const href = target.getAttribute('href');
        if (href && href.startsWith('#')) {
            const targetElement = document.querySelector(href);
            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } else if (href) {
            // Pour les liens externes comme le retour au forum
            window.location.href = href;
        }
    }
}

// Initialisation automatique quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    window.threadQuickNav = new ThreadQuickNav();
}); 