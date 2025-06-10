/**
 * Effets visuels et animations pour les threads
 */

class ThreadEffects {
    constructor() {
        this.init();
    }

    init() {
        this.setupRippleEffect();
        this.setupAvatarAnimations();
        this.setupProtections();
        this.injectStyles();
    }

    setupRippleEffect() {
        // Définir la fonction createRipple si elle n'existe pas
        if (typeof window.createRipple === 'undefined') {
            window.createRipple = (event) => {
                const button = event.currentTarget;
                
                // Créer l'élément ripple
                const ripple = document.createElement('span');
                const rect = button.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = event.clientX - rect.left - size / 2;
                const y = event.clientY - rect.top - size / 2;
                
                ripple.className = 'ripple';
                ripple.style.cssText = `
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                `;
                
                // S'assurer que le bouton a position relative
                if (getComputedStyle(button).position === 'static') {
                    button.style.position = 'relative';
                }
                
                // Assurer overflow hidden pour contenir l'effet
                button.style.overflow = 'hidden';
                
                // Ajouter l'effet ripple
                button.appendChild(ripple);
                
                // Supprimer l'effet après l'animation
                setTimeout(() => {
                    if (ripple.parentNode) {
                        ripple.parentNode.removeChild(ripple);
                    }
                }, 600);
            };
        }
        
        // Ajouter l'effet ripple aux boutons
        document.querySelectorAll('.btn, .faction-tag, .thread-stat-item').forEach(btn => {
            btn.addEventListener('click', window.createRipple);
        });
    }

    setupAvatarAnimations() {
        // Animation des avatars au hover
        const avatars = document.querySelectorAll('.avatar-container, .thread-author-avatar, .npc-avatar');
        avatars.forEach(avatar => {
            avatar.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05) rotateZ(2deg)';
                this.style.transition = 'transform 0.3s ease';
            });
            
            avatar.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1) rotateZ(0deg)';
            });
        });
    }

    setupProtections() {
        // === PROTECTION POUR LES CHAMPS DE TEXTE ===
        
        // Empêcher toute interférence avec les champs de texte et formulaires
        document.addEventListener('click', function(e) {
            // Si c'est un champ de texte, textarea, input ou tout élément de formulaire
            if (e.target.matches('input, textarea, select, button[type="submit"], .form-control, .btn')) {
                e.stopPropagation();
                console.log('Clic protégé sur:', e.target.tagName, e.target.type || 'N/A');
            }
        }, true); // Use capture phase
        
        // Protection spécifique pour les focus sur les champs
        document.addEventListener('focus', function(e) {
            if (e.target.matches('input, textarea, select, .form-control')) {
                e.stopPropagation();
                console.log('Focus protégé sur:', e.target.tagName);
            }
        }, true);
    }

    injectStyles() {
        // Injecter les styles CSS pour les effets
        const effectsStyle = document.createElement('style');
        effectsStyle.textContent = `
            .ripple {
                position: absolute;
                border-radius: 50%;
                background-color: rgba(255, 255, 255, 0.4);
                transform: scale(0);
                animation: ripple-animation 0.6s linear;
                pointer-events: none;
            }
            
            @keyframes ripple-animation {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            /* Style pour l'indicateur de filtre de personnage */
            #character-filter-indicator {
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: var(--warning-color, #ffc107);
                color: #000;
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 14px;
                font-weight: 500;
                z-index: 1000;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                transition: all 0.3s ease;
                opacity: 0;
                visibility: hidden;
            }
            
            /* Classes pour le filtrage des posts sans affecter le style original */
            .post-container.character-filtered-hidden {
                display: none !important;
            }
            
            .post-container.character-filtered-visible {
                /* Garde le style original, juste pour être explicite */
            }
            
            /* Animations pour les avatars */
            .avatar-container, .thread-author-avatar, .npc-avatar {
                transition: transform 0.3s ease;
            }
            
            /* Hover effects pour les éléments interactifs */
            .btn, .faction-tag, .thread-stat-item {
                position: relative;
                overflow: hidden;
            }
        `;
        document.head.appendChild(effectsStyle);
    }
}

// Initialisation automatique quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    window.threadEffects = new ThreadEffects();
}); 