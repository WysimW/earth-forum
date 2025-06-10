/**
 * Gestion des modals pour les threads (suppression, modération)
 */

class ThreadModals {
    constructor() {
        this.universSlug = '';
        this.threadId = '';
        
        this.init();
    }

    init() {
        // Récupérer les données depuis les attributs du DOM
        this.initializeData();
        this.setupDeletePostModal();
        this.setupModerationModal();
    }

    initializeData() {
        // Récupération des données depuis le template
        const container = document.querySelector('.thread-show');
        if (container) {
            this.universSlug = container.dataset.universSlug || '';
            this.threadId = container.dataset.threadId || '';
        }
    }

    setupDeletePostModal() {
        const deletePostModal = document.getElementById('deletePostModal');
        if (!deletePostModal) return;

        // Récupérer les tokens CSRF depuis le template
        const csrfTokens = this.getCSRFTokens();
        
        deletePostModal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            const postId = button.getAttribute('data-post-id');
            const deleteForm = document.getElementById('deletePostForm');
            const tokenInput = document.getElementById('deletePostToken');
            
            if (deleteForm && postId) {
                deleteForm.action = this.generatePostDeleteUrl(postId);
            }
            
            // Utiliser le token CSRF pré-généré
            if (tokenInput && postId && csrfTokens[postId]) {
                tokenInput.value = csrfTokens[postId];
            }
        });
    }

    setupModerationModal() {
        const moderatePostModal = document.getElementById('moderatePostModal');
        if (!moderatePostModal) return;

        // Récupérer les tokens CSRF pour la modération
        const moderationTokens = this.getModerationTokens();
        
        let currentPostId = null;
        let selectedText = '';

        moderatePostModal.addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            const postId = button.getAttribute('data-post-id');
            currentPostId = postId;
            
            // Mise à jour du titre avec l'ID du message
            document.getElementById('modalPostId').textContent = '#' + postId;
            
            // Configuration du formulaire
            const moderateForm = document.getElementById('moderatePostForm');
            const tokenInput = document.getElementById('moderatePostToken');
            
            if (moderateForm && postId) {
                moderateForm.action = this.generatePostModerateUrl(postId);
            }
            
            // Token CSRF
            if (tokenInput && postId && moderationTokens[postId]) {
                tokenInput.value = moderationTokens[postId];
            }
            
            this.setupModerationOptions(postId);
            this.setupPostPreview(postId);
            this.setupTextSelection();
            
            // Réinitialiser la sélection
            this.clearQuotedText();
        });

        // Nettoyer à la fermeture du modal
        moderatePostModal.addEventListener('hidden.bs.modal', () => {
            this.cleanupModerationModal();
            currentPostId = null;
        });
    }

    setupModerationOptions(postId) {
        // Gestion de l'affichage des options selon l'état du message
        const postContainer = document.querySelector('.post-container[data-post-id="' + postId + '"]');
        const isHidden = postContainer && postContainer.getAttribute('data-is-hidden') === 'true';
        
        const hideOption = document.getElementById('hide').parentElement;
        const unhideOption = document.getElementById('unhideOption');
        const warnOption = document.getElementById('warn').parentElement;
        const editRequestOption = document.getElementById('edit_request').parentElement;
        
        if (isHidden) {
            // Si le message est masqué, on affiche uniquement l'option de démasquage
            hideOption.style.display = 'none';
            warnOption.style.display = 'none';
            editRequestOption.style.display = 'none';
            unhideOption.style.display = 'block';
            
            // Sélectionner automatiquement l'option de démasquage
            document.getElementById('unhide').checked = true;
            
            // Modifier le texte d'aide pour le motif
            this.updateModerationLabels(true);
        } else {
            // Si le message n'est pas masqué, on affiche les options normales
            hideOption.style.display = 'block';
            warnOption.style.display = 'block';
            editRequestOption.style.display = 'block';
            unhideOption.style.display = 'none';
            
            // Sélectionner l'avertissement par défaut
            document.getElementById('warn').checked = true;
            
            // Restaurer le texte d'aide normal
            this.updateModerationLabels(false);
        }
    }

    updateModerationLabels(isUnhiding) {
        const reasonLabel = document.querySelector('label[for="moderation_reason"]');
        const reasonHelp = reasonLabel.nextElementSibling.nextElementSibling;
        const reasonField = document.getElementById('moderation_reason');
        
        if (isUnhiding) {
            reasonLabel.textContent = 'Motif du démasquage (optionnel)';
            reasonHelp.textContent = 'Expliquez pourquoi vous démasquez ce message (optionnel).';
            reasonField.required = false;
            reasonField.placeholder = 'Raison du démasquage (optionnel)...';
        } else {
            reasonLabel.textContent = 'Motif de la modération';
            reasonHelp.textContent = 'Ce message sera visible par l\'auteur du post et les autres modérateurs.';
            reasonField.required = true;
            reasonField.placeholder = 'Expliquez le motif de cette action de modération...';
        }
    }

    setupPostPreview(postId) {
        const previewContent = document.getElementById('postPreviewContent');
        
        // Indicateur de chargement
        previewContent.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin me-2"></i>Chargement du contenu...</div>';
        
        // Petit délai pour montrer le chargement
        setTimeout(() => {
            // Rechercher le post à partir de l'ancre et récupérer le container suivant
            const postAnchor = document.querySelector('#post-' + postId);
            const originalPost = postAnchor ? postAnchor.nextElementSibling : null;
            
            if (previewContent && originalPost && originalPost.classList.contains('post-container')) {
                // Récupérer le contenu du message original
                const postContent = originalPost.querySelector('.post-content');
                if (postContent) {
                    // Cloner le contenu pour l'aperçu
                    previewContent.innerHTML = postContent.innerHTML;
                    
                    // Récupérer l'auteur du message
                    const authorElement = originalPost.querySelector('.name-label');
                    const authorName = authorElement ? authorElement.textContent.trim() : 'Utilisateur inconnu';
                    previewContent.setAttribute('data-author', authorName);
                    
                    console.log('Contenu du message chargé pour le post #' + postId);
                } else {
                    previewContent.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>Impossible de charger le contenu du message.</div>';
                }
            } else {
                previewContent.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times me-2"></i>Message introuvable (ID: ' + postId + ').</div>';
            }
        }, 100);
    }

    setupTextSelection() {
        // Gestion de la sélection de texte uniquement dans le modal de modération
        let selectionListener = null;
        
        const updateSelectedText = () => {
            const moderatePostModal = document.getElementById('moderatePostModal');
            if (!moderatePostModal.classList.contains('show')) {
                return;
            }
            
            const selection = window.getSelection();
            const previewContent = document.getElementById('postPreviewContent');
            
            if (selection.rangeCount > 0 && previewContent && previewContent.contains(selection.anchorNode)) {
                const selectedText = selection.toString().trim();
                const quoteBtn = document.getElementById('quoteSelectedBtn');
                
                if (selectedText.length > 0) {
                    quoteBtn.disabled = false;
                    quoteBtn.classList.remove('btn-outline-secondary');
                    quoteBtn.classList.add('btn-outline-success');
                    previewContent.classList.add('selecting');
                    
                    // Mettre à jour le texte du bouton avec la longueur
                    quoteBtn.innerHTML = `<i class="fas fa-quote-right me-1"></i>Quoter (${selectedText.length} car.)`;
                } else {
                    this.resetQuoteButton(quoteBtn, previewContent);
                }
            } else {
                const quoteBtn = document.getElementById('quoteSelectedBtn');
                if (quoteBtn) {
                    this.resetQuoteButton(quoteBtn, previewContent);
                }
            }
        };

        // Event listeners pour la sélection
        selectionListener = updateSelectedText;
        document.addEventListener('selectionchange', selectionListener);

        // Bouton "Tout sélectionner"
        document.getElementById('selectAllBtn').addEventListener('click', () => {
            const previewContent = document.getElementById('postPreviewContent');
            const range = document.createRange();
            range.selectNodeContents(previewContent);
            
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
            
            updateSelectedText();
        });

        // Bouton "Quoter la sélection"
        document.getElementById('quoteSelectedBtn').addEventListener('click', () => {
            const selectedText = window.getSelection().toString();
            if (selectedText) {
                this.showQuotedText(selectedText);
            }
        });

        // Bouton "Effacer la sélection"
        document.getElementById('clearQuoteBtn').addEventListener('click', () => {
            this.clearQuotedText();
        });

        // Désactiver l'event listener de sélection à la fermeture
        const moderatePostModal = document.getElementById('moderatePostModal');
        moderatePostModal.addEventListener('hidden.bs.modal', () => {
            if (selectionListener) {
                document.removeEventListener('selectionchange', selectionListener);
                selectionListener = null;
            }
        });
    }

    resetQuoteButton(quoteBtn, previewContent) {
        quoteBtn.disabled = true;
        quoteBtn.classList.remove('btn-outline-success');
        quoteBtn.classList.add('btn-outline-secondary');
        if (previewContent) {
            previewContent.classList.remove('selecting');
        }
        quoteBtn.innerHTML = '<i class="fas fa-quote-right me-1"></i>Quoter la sélection';
    }

    showQuotedText(text) {
        const quotedTextContainer = document.getElementById('quotedTextContainer');
        const quotedText = document.getElementById('quotedText');
        const quotedTextInput = document.getElementById('quotedTextInput');
        
        if (text && text.trim().length > 0) {
            quotedText.textContent = text;
            quotedTextInput.value = text;
            quotedTextContainer.style.display = 'block';
        }
    }

    clearQuotedText() {
        const quotedTextContainer = document.getElementById('quotedTextContainer');
        const quotedTextInput = document.getElementById('quotedTextInput');
        const quoteBtn = document.getElementById('quoteSelectedBtn');
        const previewContent = document.getElementById('postPreviewContent');
        
        quotedTextContainer.style.display = 'none';
        quotedTextInput.value = '';
        
        quoteBtn.disabled = true;
        quoteBtn.classList.remove('btn-outline-success');
        quoteBtn.classList.add('btn-outline-secondary');
        quoteBtn.innerHTML = '<i class="fas fa-quote-right me-1"></i>Quoter la sélection';
        
        // Enlever l'état visuel de sélection
        previewContent.classList.remove('selecting');
        
        // Effacer la sélection
        window.getSelection().removeAllRanges();
    }

    cleanupModerationModal() {
        this.clearQuotedText();
        
        // Réinitialiser les options de modération
        document.getElementById('unhideOption').style.display = 'none';
        document.getElementById('hide').parentElement.style.display = 'block';
        document.getElementById('warn').parentElement.style.display = 'block';
        document.getElementById('edit_request').parentElement.style.display = 'block';
        
        // Restaurer les textes par défaut
        this.updateModerationLabels(false);
        
        // Réinitialiser les champs
        document.getElementById('moderation_reason').value = '';
        document.getElementById('internal_note').value = '';
    }

    // Méthodes utilitaires pour récupérer les données depuis le template
    getCSRFTokens() {
        // Ces tokens doivent être injectés depuis le template Twig
        const tokensElement = document.getElementById('csrf-tokens');
        if (tokensElement) {
            return JSON.parse(tokensElement.textContent);
        }
        return {};
    }

    getModerationTokens() {
        // Ces tokens doivent être injectés depuis le template Twig
        const tokensElement = document.getElementById('moderation-tokens');
        if (tokensElement) {
            return JSON.parse(tokensElement.textContent);
        }
        return {};
    }

    generatePostDeleteUrl(postId) {
        return `/univers/${this.universSlug}/post/${postId}/delete`;
    }

    generatePostModerateUrl(postId) {
        return `/univers/${this.universSlug}/post/${postId}/moderate`;
    }
}

// Initialisation automatique quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    window.threadModals = new ThreadModals();
}); 