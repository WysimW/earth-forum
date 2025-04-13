/**
 * DC Earth Forum - Éditeur WYSIWYG
 * Basé sur la bibliothèque Quill.js (gratuit et open-source)
 */

// Stockage global pour les instances des éditeurs Quill
window.dcQuillEditors = window.dcQuillEditors || {};

document.addEventListener('DOMContentLoaded', function() {
    // Configuration des formats personnalisés
    const rpFormat = {
        // Format RP (texte italique avec couleur spécifique)
        rpText: {
            tag: 'SPAN',
            class: 'roleplay-text'
        },
        // Format HRP (texte surligné avec couleur spécifique)
        hrpText: {
            tag: 'SPAN',
            class: 'hrp-text'
        }
    };

    // Fonction pour initialiser les éditeurs
    function initEditors() {
        // Sélection de tous les textarea avec la classe wysiwyg-editor
        const editors = document.querySelectorAll('textarea.wysiwyg-editor, textarea.wysiwyg-editor-full, textarea.wysiwyg-editor-quick');
        
        // Configuration de base pour tous les éditeurs
        editors.forEach(textarea => {
            // Vérifier si cet éditeur a déjà été initialisé
            if (textarea.dataset.quillInitialized === "true") {
                console.log("Skipping already initialized editor:", textarea.id || "unnamed");
                return;
            }

            // Créer un conteneur pour notre éditeur
            const container = document.createElement('div');
            container.className = 'dc-editor-container';
            textarea.parentNode.insertBefore(container, textarea);
            
            // Déplacer les attributs id et name à un champ caché
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = textarea.name;
            hiddenInput.id = textarea.id;
            container.appendChild(hiddenInput);
            
            // Créer le div pour l'éditeur Quill
            const editorDiv = document.createElement('div');
            container.appendChild(editorDiv);
            
            // Créer le compteur de caractères
            const charCounter = document.createElement('div');
            charCounter.className = 'dc-editor-char-counter';
            charCounter.innerHTML = '0 caractères';
            container.appendChild(charCounter);
            
            // Déterminer le mode d'éditeur (RP ou HRP)
            let mode = '';
            if (textarea.dataset.mode === 'roleplay') {
                mode = 'roleplay';
                container.classList.add('roleplay');
            } else if (textarea.dataset.mode === 'hrp') {
                mode = 'hrp';
                container.classList.add('hrp');
            }
            
            // Configuration de la barre d'outils
            let toolbarOptions = [
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'script': 'sub'}, { 'script': 'super' }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'align': [] }],
                ['link', 'image'],
                ['clean']
            ];
            
            // Si nous sommes dans un contexte RP/HRP, ajouter les boutons personnalisés
            if (mode === 'roleplay' || mode === 'hrp') {
                toolbarOptions.push(['rp', 'hrp']);
            }
            
            // Déterminer si c'est un éditeur complet, standard ou simplifié
            let editorType = 'standard';
            if (textarea.classList.contains('wysiwyg-editor-full')) {
                editorType = 'full';
            } else if (textarea.classList.contains('wysiwyg-editor-quick')) {
                editorType = 'quick';
                // Version simplifiée pour les éditeurs "quick"
                toolbarOptions = [
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'align': [] }],
                    ['link', 'image'],
                    ['clean']
                ];
                
                // Ajouter RP/HRP même en mode simplifié si nécessaire
                if (mode === 'roleplay' || mode === 'hrp') {
                    toolbarOptions.push(['rp', 'hrp']);
                }
            }
            
            // Récupérer le contenu initial
            let initialContent = textarea.value;
            
            // Initialiser Quill
            const quill = new Quill(editorDiv, {
                modules: {
                    toolbar: {
                        container: toolbarOptions,
                        handlers: {
                            'rp': function() {
                                formatText('rpText');
                            },
                            'hrp': function() {
                                formatText('hrpText');
                            }
                        }
                    }
                },
                placeholder: textarea.getAttribute('placeholder') || 'Écrivez votre message ici...',
                theme: 'snow',
                formats: Object.assign({}, Quill.import('formats'), rpFormat)
            });
            
            // Fonction pour appliquer un format personnalisé
            function formatText(format) {
                const range = quill.getSelection();
                if (range) {
                    if (range.length > 0) {
                        quill.format(format, true);
                    } else {
                        // Si aucun texte n'est sélectionné, activer le format pour la prochaine saisie
                        const currentFormat = quill.getFormat();
                        quill.format(format, !currentFormat[format]);
                    }
                }
            }
            
            // Initialiser avec le contenu existant
            if (initialContent) {
                quill.root.innerHTML = initialContent;
            }
            
            // Fonction de mise à jour du compteur de caractères
            function updateCharCounter() {
                const text = quill.getText().trim();
                const length = text.length;
                charCounter.textContent = length + ' caractère' + (length > 1 ? 's' : '');
                
                // Ajouter un avertissement visuel lorsque le texte devient trop long
                charCounter.classList.remove('warning', 'danger');
                if (length > 5000 && length <= 10000) {
                    charCounter.classList.add('warning');
                } else if (length > 10000) {
                    charCounter.classList.add('danger');
                }
            }
            
            // Mettre à jour le contenu du champ caché et le compteur de caractères lors de la modification
            quill.on('text-change', function() {
                hiddenInput.value = quill.root.innerHTML;
                textarea.value = quill.root.innerHTML; // Synchroniser avec le textarea original également
                updateCharCounter();
                
                // Déclencher un événement de changement pour compatibilité avec les validateurs de formulaire
                const event = new Event('change', {
                    bubbles: true,
                    cancelable: true,
                });
                hiddenInput.dispatchEvent(event);
                textarea.dispatchEvent(event); // Déclencher aussi l'événement sur le textarea original
            });
            
            // Initialiser le compteur
            updateCharCounter();
            
            // Gérer le textarea original (solution pour le problème de required)
            if (textarea.hasAttribute('required')) {
                // Retirer l'attribut required du textarea puisqu'il sera caché
                textarea.removeAttribute('required');
                
                // Ajouter une classe spéciale pour le marquer comme validable
                textarea.classList.add('quill-required');
                
                // Ajouter la validation personnalisée au formulaire parent
                const form = textarea.closest('form');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        const content = quill.getText().trim();
                        if (content.length === 0) {
                            e.preventDefault();
                            // Afficher une erreur ou faire un focus sur l'éditeur
                            quill.focus();
                            // Vous pourriez aussi ajouter un message d'erreur visuel ici
                            console.error("Le champ est obligatoire");
                        }
                    });
                }
            }
            
            // Cacher le textarea original mais garder sa valeur synchronisée
            textarea.style.display = 'none';
            
            // Marquer cet éditeur comme initialisé pour éviter la double initialisation
            textarea.dataset.quillInitialized = "true";
            
            // Stocker l'éditeur dans l'élément original pour y accéder plus tard si nécessaire
            textarea._quill = quill;
            
            // Stocker aussi l'instance dans le registre global
            const editorId = textarea.id || `quill_editor_${Math.random().toString(36).substr(2, 9)}`;
            window.dcQuillEditors[editorId] = quill;
            
            // Ajouter un attribut d'ID pour faciliter la récupération ultérieure
            editorDiv.dataset.quillId = editorId;
        });
    }
    
    // Initialiser les éditeurs au chargement de la page
    initEditors();
    
    // Exposer la fonction d'initialisation pour les formulaires chargés dynamiquement
    window.initWysiwygEditors = initEditors;
    
    // Fonction globale pour récupérer un éditeur Quill par ID ou sélecteur
    window.getQuillEditor = function(selector) {
        // Si c'est un ID direct d'un éditeur existant
        if (window.dcQuillEditors[selector]) {
            return window.dcQuillEditors[selector];
        }
        
        // Si c'est un sélecteur CSS
        const textarea = document.querySelector(selector);
        if (textarea && textarea._quill) {
            return textarea._quill;
        }
        
        // Si c'est un sélecteur qui renvoie vers un conteneur d'éditeur
        const container = document.querySelector(selector + ' .ql-container');
        if (container) {
            return Quill.find(container);
        }
        
        return null;
    };
});