/**
 * Gestion du filtrage par personnage dans les threads RP
 */

class CharacterFilter {
    constructor() {
        this.currentCharacterFilter = null;
        this.availableCharacters = [];
        this.currentCharacterIndex = -1;
        this.isFilterActive = false;
        this.postsQuickNav = document.getElementById('posts-quick-nav');
        
        this.init();
    }

    init() {
        if (!this.postsQuickNav) return;
        
        // Exposer la fonction reset globalement pour le bouton dans l'indicateur
        window.resetCharacterFilter = () => this.resetFilter();
    }

    initializeCharacters() {
        const posts = document.querySelectorAll('.post-container');
        const characterNames = new Set();
        
        // Collecter tous les noms de personnages
        posts.forEach(post => {
            const characterName = post.querySelector('.post-character-info .name-label');
            if (characterName) {
                characterNames.add(characterName.textContent.trim());
            }
        });
        
        this.availableCharacters = Array.from(characterNames);
        return this.availableCharacters.length > 0;
    }

    toggleFilter() {
        if (!this.initializeCharacters()) return;
        
        if (!this.isFilterActive) {
            // Activer le filtre avec le premier personnage
            this.currentCharacterIndex = 0;
            this.isFilterActive = true;
            this.filterByCharacter(this.availableCharacters[this.currentCharacterIndex]);
            this.showNavigationArrows();
        } else {
            // Désactiver le filtre
            this.resetFilter();
        }
    }

    navigateCharacter(direction) {
        if (!this.isFilterActive || this.availableCharacters.length === 0) return;
        
        if (direction === 'next') {
            this.currentCharacterIndex = (this.currentCharacterIndex + 1) % this.availableCharacters.length;
        } else if (direction === 'prev') {
            this.currentCharacterIndex = this.currentCharacterIndex === 0 ? this.availableCharacters.length - 1 : this.currentCharacterIndex - 1;
        }
        
        this.filterByCharacter(this.availableCharacters[this.currentCharacterIndex]);
    }

    filterByCharacter(characterName) {
        const posts = document.querySelectorAll('.post-container');
        let visibleCount = 0;
        
        posts.forEach(post => {
            const characterNameElement = post.querySelector('.post-character-info .name-label');
            const postCharacterName = characterNameElement ? characterNameElement.textContent.trim() : '';
            
            // Retirer toutes les classes de filtrage d'abord
            post.classList.remove('character-filtered-hidden', 'character-filtered-visible');
            
            if (postCharacterName === characterName) {
                post.classList.add('character-filtered-visible');
                visibleCount++;
            } else {
                post.classList.add('character-filtered-hidden');
            }
        });
        
        this.currentCharacterFilter = characterName;
        
        // Marquer le bouton comme actif
        const filterButton = this.postsQuickNav.querySelector('.character-filter');
        if (filterButton) {
            filterButton.classList.add('active');
            filterButton.setAttribute('data-tooltip', `${characterName} (${visibleCount}) | Clic: désactiver`);
        }
        
        // Mettre à jour les tooltips des flèches
        this.updateNavigationTooltips();
        
        // Afficher un indicateur avec progression
        const progression = `${this.currentCharacterIndex + 1}/${this.availableCharacters.length}`;
        this.showFilterIndicator(`Affichage des posts de ${characterName} (${visibleCount}) - ${progression}`);
    }

    resetFilter() {
        const posts = document.querySelectorAll('.post-container');
        
        posts.forEach(post => {
            // Retirer toutes les classes de filtrage pour revenir au style original
            post.classList.remove('character-filtered-hidden', 'character-filtered-visible');
        });
        
        this.currentCharacterFilter = null;
        this.currentCharacterIndex = -1;
        this.isFilterActive = false;
        
        // Retirer la classe active du bouton
        const filterButton = this.postsQuickNav.querySelector('.character-filter');
        if (filterButton) {
            filterButton.classList.remove('active');
            filterButton.setAttribute('data-tooltip', 'Filtrer par personnage');
        }
        
        this.hideNavigationArrows();
        this.hideFilterIndicator();
    }

    showNavigationArrows() {
        const prevButton = this.postsQuickNav.querySelector('.character-nav-prev');
        const nextButton = this.postsQuickNav.querySelector('.character-nav-next');
        
        if (prevButton) prevButton.style.display = 'flex';
        if (nextButton) nextButton.style.display = 'flex';
    }

    hideNavigationArrows() {
        const prevButton = this.postsQuickNav.querySelector('.character-nav-prev');
        const nextButton = this.postsQuickNav.querySelector('.character-nav-next');
        
        if (prevButton) prevButton.style.display = 'none';
        if (nextButton) nextButton.style.display = 'none';
    }

    updateNavigationTooltips() {
        const prevButton = this.postsQuickNav.querySelector('.character-nav-prev');
        const nextButton = this.postsQuickNav.querySelector('.character-nav-next');
        
        if (this.availableCharacters.length > 1) {
            const prevIndex = this.currentCharacterIndex === 0 ? this.availableCharacters.length - 1 : this.currentCharacterIndex - 1;
            const nextIndex = (this.currentCharacterIndex + 1) % this.availableCharacters.length;
            
            if (prevButton) {
                prevButton.setAttribute('data-tooltip', `Précédent: ${this.availableCharacters[prevIndex]}`);
            }
            if (nextButton) {
                nextButton.setAttribute('data-tooltip', `Suivant: ${this.availableCharacters[nextIndex]}`);
            }
        }
    }

    showFilterIndicator(message) {
        let indicator = document.getElementById('character-filter-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'character-filter-indicator';
            document.body.appendChild(indicator);
        }
        
        indicator.innerHTML = `
            <i class="fas fa-filter me-2"></i>${message}
            <button onclick="resetCharacterFilter()" style="background: none; border: none; color: #000; margin-left: 8px; cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
        `;
        indicator.style.opacity = '1';
        indicator.style.visibility = 'visible';
    }

    hideFilterIndicator() {
        const indicator = document.getElementById('character-filter-indicator');
        if (indicator) {
            indicator.style.opacity = '0';
            indicator.style.visibility = 'hidden';
        }
    }
}

// Initialisation automatique quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    window.characterFilter = new CharacterFilter();
}); 