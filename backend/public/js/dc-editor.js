/**
 * DC Earth Forum - Éditeur WYSIWYG
 * Basé sur la bibliothèque Quill.js (gratuit et open-source)
 */

// Stockage global pour les instances des éditeurs Quill
window.dcQuillEditors = window.dcQuillEditors || {};

document.addEventListener('DOMContentLoaded', function () {
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
        },
        // Format de dialogue RP
        dialogue: {
            tag: 'SPAN',
            class: 'dialogue-text',
            attributes: {
                'data-character': true
            }
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
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'script': 'sub' }, { 'script': 'super' }],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'align': [] }],
                ['link', 'image'],
                ['clean'],
                [{
                    'dialogue-rp': {
                        icon: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                        </svg>`
                    }
                }]
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
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                    [{ 'script': 'sub' }, { 'script': 'super' }],
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
                            'rp': function () {
                                formatText('rpText');
                            },
                            'hrp': function () {
                                formatText('hrpText');
                            },
                            'dialogue-rp': function () {
                                showDialogueSelector(quill);
                            }
                        }
                    }
                },
                placeholder: textarea.getAttribute('placeholder') || 'Écrivez votre message ici...',
                theme: 'snow',
                formats: Object.assign({}, Quill.import('formats'), rpFormat)
            });

            // Importer et configurer le format de police
            // Importer et configurer le format de police
            const Font = Quill.import('formats/font');
            Font.whitelist = [
                'arial',
                'times-new-roman',
                'courier-new',
                'georgia',
                'trebuchet-ms',
                'verdana'
            ];
            Quill.register(Font, true);


            // Ajouter des styles personnalisés pour s'assurer que les polices sont correctement appliquées
            const styleNode = document.createElement('style');
            styleNode.innerHTML = `
                .ql-font-Arial\\,\\ sans-serif { font-family: Arial, sans-serif !important; }
                .ql-font-\\\'Times\\ New\\ Roman\\\',\\ serif { font-family: 'Times New Roman', serif !important; }
                .ql-font-\\\'Courier\\ New\\\',\\ monospace { font-family: 'Courier New', monospace !important; }
                .ql-font-Georgia\\,\\ serif { font-family: Georgia, serif !important; }
                .ql-font-\\\'Trebuchet\\ MS\\\',\\ sans-serif { font-family: 'Trebuchet MS', sans-serif !important; }
                .ql-font-Verdana\\,\\ sans-serif { font-family: Verdana, sans-serif !important; }
            `;
            document.head.appendChild(styleNode);

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
            quill.on('text-change', function () {
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
                    form.addEventListener('submit', function (e) {
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
    window.getQuillEditor = function (selector) {
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

// Fonction pour afficher le sélecteur de dialogue
function showDialogueSelector(quill) {
    const range = quill.getSelection();
    if (!range) return;

    // Créer le modal de sélection
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sélectionner le personnage</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Personnages disponibles</label>
                        <select class="form-select" id="dialogueCharacter">
                            <option value="">-- Chargement en cours --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ou créer un PNJ éphémère</label>
                        <input type="text" class="form-control" id="ephemeralNpc" placeholder="Nom du PNJ">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="applyDialogue">Appliquer</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();

    // Récupérer les personnages disponibles via l'API
    const apiUrl = '/ajax/characters/available';
    console.log('Appel API:', apiUrl);
    fetch(apiUrl)
        .then(response => {
            console.log('Réponse API:', response.status, response.statusText);
            if (!response.ok) {
                throw new Error('Erreur réseau: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Données reçues:', data);

            // Stocker les données pour une utilisation ultérieure
            window.availableCharacters = data;

            const select = document.getElementById('dialogueCharacter');
            select.innerHTML = '<option value="">-- Sélectionner un personnage --</option>';

            // Ajouter les personnages
            if (data.characters && data.characters.length > 0) {
                data.characters.forEach(char => {
                    select.innerHTML += `<option value="${char.id}" data-type="character">${char.name}</option>`;
                });
            }

            // Ajouter les PNJ
            if (data.npcs && data.npcs.length > 0) {
                data.npcs.forEach(npc => {
                    select.innerHTML += `<option value="${npc.id}" data-type="npc">${npc.name}</option>`;
                });
            }

            if (data.characters.length === 0 && data.npcs.length === 0) {
                select.innerHTML = '<option value="">-- Aucun personnage disponible --</option>';
            }
        })
        .catch(error => {
            console.error('Erreur lors de la récupération des personnages:', error);
            const select = document.getElementById('dialogueCharacter');
            select.innerHTML = '<option value="">-- Erreur de chargement --</option>';
        });

    // Gérer la sélection
    document.getElementById('applyDialogue').addEventListener('click', function () {
        const characterSelect = document.getElementById('dialogueCharacter');
        const ephemeralNpc = document.getElementById('ephemeralNpc');
        let characterName = '';
        let characterStyle = null;

        if (characterSelect.value) {
            const selectedOption = characterSelect.options[characterSelect.selectedIndex];
            characterName = selectedOption.text;

            // Récupérer le style personnalisé si disponible
            const characterId = selectedOption.value;
            const characterType = selectedOption.dataset.type;

            // Rechercher dans les données de l'API
            if (window.availableCharacters) {
                const characters = characterType === 'character'
                    ? window.availableCharacters.characters
                    : window.availableCharacters.npcs;

                const character = characters.find(c => c.id == characterId);
                console.log('Character style reçu:', character.style);
                if (character && character.style) {
                    characterStyle = character.style;
                    console.log('Style appliqué pour', characterName, ':', characterStyle);
                }
            }
        } else if (ephemeralNpc.value) {
            characterName = ephemeralNpc.value;
        }

        if (characterName) {
            console.log('Application du dialogue pour:', characterName, 'avec style:', characterStyle);

            // Obtenir la sélection actuelle
            const range = quill.getSelection(true);
            
            // Style par défaut si aucun style personnalisé n'est disponible
            const defaultStyle = {
                nameColor: '#2c3e50',
                nameBold: true,
                nameItalic: false,
                textColor: '#6c757d',
                textItalic: true,
                textBold: false,
                showName: true,
                quoteType: 'french',
                fontFamily: 'inherit'
            };
            
            // Utiliser le style personnalisé ou le style par défaut
            const style = characterStyle || defaultStyle;
            
            // Sauvegarder la position actuelle du curseur
            const cursorPosition = range.index;
            
            // IMPORTANT: Stocker le texte sélectionné AVANT de le supprimer
            let selectedText = '';
            if (range.length > 0) {
                selectedText = quill.getText(range.index, range.length);
                console.log('Texte sélectionné:', selectedText);
            }
            
            // Déterminer les guillemets à utiliser
            let openQuote = '';
            let closeQuote = '';
            
            switch (style.quoteType) {
                case 'double':
                    openQuote = '"';
                    closeQuote = '"';
                    break;
                case 'single':
                    openQuote = "'";
                    closeQuote = "'";
                    break;
                case 'french':
                    openQuote = '« ';
                    closeQuote = ' »';
                    break;
                case 'dash':
                    openQuote = '— ';
                    closeQuote = '';
                    break;
                default:
                    openQuote = '';
                    closeQuote = '';
            }
            
            // Supprimer le texte sélectionné si nécessaire
            if (range.length > 0) {
                quill.deleteText(range.index, range.length);
            }
            
            // Si nous devons afficher le nom
            if (style.showName) {
                // Insérer le nom avec son style
                quill.insertText(range.index, characterName + ' : ', {
                    'bold': style.nameBold,
                    'italic': style.nameItalic,
                    'color': style.nameColor,
                    'font': style.fontFamily !== 'inherit' ? style.fontFamily : false
                });
                
                // Insérer le texte avec guillemets
                let insertPosition = range.index + (characterName.length + 3);
                
                // Insérer le guillemet ouvrant
                quill.insertText(insertPosition, openQuote, {
                    'bold': style.textBold,
                    'italic': style.textItalic,
                    'color': style.textColor,
                    'font': style.fontFamily !== 'inherit' ? style.fontFamily : false
                });
                insertPosition += openQuote.length;
                
                // Insérer le texte sélectionné s'il y en a
                if (selectedText) {
                    quill.insertText(insertPosition, selectedText, {
                        'bold': style.textBold,
                        'italic': style.textItalic,
                        'color': style.textColor,
                        'font': style.fontFamily !== 'inherit' ? style.fontFamily : false
                    });
                    insertPosition += selectedText.length;
                }
                
                // Insérer le guillemet fermant s'il y a du texte sélectionné
                if (selectedText) {
                    quill.insertText(insertPosition, closeQuote, {
                        'bold': style.textBold,
                        'italic': style.textItalic,
                        'color': style.textColor,
                        'font': style.fontFamily !== 'inherit' ? style.fontFamily : false
                    });
                    insertPosition += closeQuote.length;
                }
                
                // Placer le curseur à la bonne position
                quill.setSelection(insertPosition);
            } else {
                // Pas de nom, juste le texte
                let insertPosition = range.index;
                
                // Insérer le guillemet ouvrant
                quill.insertText(insertPosition, openQuote, {
                    'bold': style.textBold,
                    'italic': style.textItalic,
                    'color': style.textColor,
                    'font': style.fontFamily !== 'inherit' ? style.fontFamily : false
                });
                insertPosition += openQuote.length;
                
                // Insérer le texte sélectionné s'il y en a
                if (selectedText) {
                    quill.insertText(insertPosition, selectedText, {
                        'bold': style.textBold,
                        'italic': style.textItalic,
                        'color': style.textColor,
                        'font': style.fontFamily !== 'inherit' ? style.fontFamily : false
                    });
                    insertPosition += selectedText.length;
                }
                
                // Insérer le guillemet fermant s'il y a du texte sélectionné
                if (selectedText) {
                    quill.insertText(insertPosition, closeQuote, {
                        'bold': style.textBold,
                        'italic': style.textItalic,
                        'color': style.textColor,
                        'font': style.fontFamily !== 'inherit' ? style.fontFamily : false
                    });
                    insertPosition += closeQuote.length;
                }
                
                // Placer le curseur à la bonne position
                quill.setSelection(insertPosition);
            }
            
            console.log('Dialogue inséré avec succès, police:', style.fontFamily);
        }

        modalInstance.hide();
        modal.remove();
    });

    // Nettoyer le modal quand il est fermé
    modal.addEventListener('hidden.bs.modal', function () {
        modal.remove();
    });
}

// Fonction pour formater un dialogue
function formatDialogue(quill, characterName, style) {
    if (!quill || !characterName) return;

    console.log('=== DÉBUT FORMAT DIALOGUE ===');
    console.log('Personnage:', characterName);
    console.log('Style reçu:', style);

    // Style par défaut
    const defaultStyle = {
        nameColor: '#2c3e50',
        nameBold: true,
        nameItalic: false,
        textColor: '#6c757d',
        textItalic: true,
        textBold: false,
        showName: true,
        quoteType: 'french',
        fontFamily: 'inherit'
    };

    // Utiliser le style fourni ou le style par défaut
    style = style || defaultStyle;
    console.log('Style final utilisé:', style);
    console.log('Police appliquée:', style.fontFamily);

    // Déterminer les guillemets à utiliser
    let openQuote = '';
    let closeQuote = '';

    switch (style.quoteType) {
        case 'double':
            openQuote = '"';
            closeQuote = '"';
            break;
        case 'single':
            openQuote = "'";
            closeQuote = "'";
            break;
        case 'french':
            openQuote = '« ';
            closeQuote = ' »';
            break;
        case 'dash':
            openQuote = '— ';
            closeQuote = '';
            break;
        default:
            openQuote = '';
            closeQuote = '';
    }

    // Récupérer la sélection actuelle
    const range = quill.getSelection();
    if (!range) return;

    // Si c'est juste le curseur (pas de sélection)
    if (range.length === 0) {
        let insertPosition = range.index;
        let cursorPosition = 0;

        // Insérer le nom du personnage si nécessaire
        if (style.showName) {
            console.log('Insertion du nom avec police:', style.fontFamily);

            // Créer un Delta pour le nom avec style inline en plus des formats Quill
            const nameDelta = [{
                insert: characterName + ' : ',
                attributes: {
                    bold: style.nameBold,
                    italic: style.nameItalic,
                    color: style.nameColor,
                    font: style.fontFamily
                }
            }];

            // Insérer le Delta
            quill.updateContents({
                ops: [{ retain: insertPosition }, ...nameDelta]
            });

            // Application directe du style pour s'assurer que la police est appliquée
            const nameNode = quill.scroll.descendants(node =>
                node.domNode && node.domNode.textContent.includes(characterName + ' : ')
            )[0];

            if (nameNode && nameNode.domNode) {
                nameNode.domNode.style.fontFamily = style.fontFamily;
            }

            // Vérifier ce qui a été réellement inséré
            const nameFormat = quill.getFormat(insertPosition, characterName.length + 3);
            console.log('Format du nom après insertion:', nameFormat);

            insertPosition += characterName.length + 3;
            cursorPosition += characterName.length + 3;
        }

        // Insérer le guillemet ouvrant
        console.log('Insertion du guillemet avec police:', style.fontFamily);

        // Créer un Delta pour le guillemet avec style inline
        const quoteDelta = [{
            insert: openQuote,
            attributes: {
                bold: style.textBold,
                italic: style.textItalic,
                color: style.textColor,
                font: style.fontFamily
            }
        }];

        // Insérer le Delta
        quill.updateContents({
            ops: [{ retain: insertPosition }, ...quoteDelta]
        });

        // Application directe du style pour s'assurer que la police est appliquée
        const quoteNode = quill.scroll.descendants(node =>
            node.domNode && node.domNode.textContent.includes(openQuote)
        )[0];

        if (quoteNode && quoteNode.domNode) {
            quoteNode.domNode.style.fontFamily = style.fontFamily;
        }

        // Vérifier ce qui a été réellement inséré
        const quoteFormat = quill.getFormat(insertPosition, openQuote.length);
        console.log('Format du guillemet après insertion:', quoteFormat);

        insertPosition += openQuote.length;
        cursorPosition += openQuote.length;

        // Placer le curseur après le guillemet ouvrant
        quill.setSelection(insertPosition, 0);
    } else {
        // Si du texte est sélectionné, le formater comme un dialogue
        let insertPosition = range.index;

        // D'abord insérer le nom du personnage avant la sélection si nécessaire
        if (style.showName) {
            console.log('Insertion du nom avec police:', style.fontFamily);

            // Créer un Delta pour le nom avec style inline
            const nameDelta = [{
                insert: characterName + ' : ',
                attributes: {
                    bold: style.nameBold,
                    italic: style.nameItalic,
                    color: style.nameColor,
                    font: style.fontFamily
                }
            }];

            // Insérer le Delta
            quill.updateContents({
                ops: [{ retain: insertPosition }, ...nameDelta]
            });

            // Application directe du style pour s'assurer que la police est appliquée
            const nameNode = quill.scroll.descendants(node =>
                node.domNode && node.domNode.textContent.includes(characterName + ' : ')
            )[0];

            if (nameNode && nameNode.domNode) {
                nameNode.domNode.style.fontFamily = style.fontFamily;
            }

            // Vérifier ce qui a été réellement inséré
            const nameFormat = quill.getFormat(insertPosition, characterName.length + 3);
            console.log('Format du nom après insertion:', nameFormat);

            insertPosition += characterName.length + 3;
            cursorPosition += characterName.length + 3;
        }

        // Insérer le guillemet ouvrant
        console.log('Insertion du guillemet avec police:', style.fontFamily);

        // Créer un Delta pour le guillemet avec style inline
        const quoteDelta = [{
            insert: openQuote,
            attributes: {
                bold: style.textBold,
                italic: style.textItalic,
                color: style.textColor,
                font: style.fontFamily
            }
        }];

        // Insérer le Delta
        quill.updateContents({
            ops: [{ retain: insertPosition }, ...quoteDelta]
        });

        // Application directe du style pour s'assurer que la police est appliquée
        const quoteNode = quill.scroll.descendants(node =>
            node.domNode && node.domNode.textContent.includes(openQuote)
        )[0];

        if (quoteNode && quoteNode.domNode) {
            quoteNode.domNode.style.fontFamily = style.fontFamily;
        }

        insertPosition += openQuote.length;

        // Ajuster la sélection pour prendre en compte le texte inséré
        const newRange = {
            index: insertPosition,
            length: range.length
        };

        // Appliquer un style au texte sélectionné
        console.log('Application du style au texte sélectionné. Police:', style.fontFamily);
        quill.formatText(newRange.index, newRange.length, {
            'italic': style.textItalic,
            'bold': style.textBold,
            'color': style.textColor,
            'font': style.fontFamily
        });

        // Application directe du style au texte sélectionné
        const selectedNodes = quill.scroll.descendants(node => {
            return node.domNode &&
                node.domNode.textContent &&
                node.domNode.textContent.length > 0 &&
                node.domNode.offsetTop >= newRange.index &&
                node.domNode.offsetTop < newRange.index + newRange.length;
        });

        selectedNodes.forEach(node => {
            if (node.domNode) {
                node.domNode.style.fontFamily = style.fontFamily;
            }
        });

        // Vérifier le format appliqué
        const textFormat = quill.getFormat(newRange.index, newRange.length);
        console.log('Format du texte après application:', textFormat);

        // Insérer le guillemet fermant après la sélection
        const closeQuoteDelta = [{
            insert: closeQuote,
            attributes: {
                bold: style.textBold,
                italic: style.textItalic,
                color: style.textColor,
                font: style.fontFamily
            }
        }];

        // Insérer le Delta
        quill.updateContents({
            ops: [{ retain: newRange.index + newRange.length }, ...closeQuoteDelta]
        });

        // Application directe du style pour s'assurer que la police est appliquée
        const closeQuoteNode = quill.scroll.descendants(node =>
            node.domNode && node.domNode.textContent.includes(closeQuote)
        )[0];

        if (closeQuoteNode && closeQuoteNode.domNode) {
            closeQuoteNode.domNode.style.fontFamily = style.fontFamily;
        }

        // Placer le curseur à la fin
        quill.setSelection(newRange.index + newRange.length + closeQuote.length);
    }

    console.log('=== FIN FORMAT DIALOGUE ===');
    const htmlFinal = quill.root.innerHTML;
    console.log('HTML FINAL après formatage:', htmlFinal);

    // Application directe du style après tout le formatage
    setTimeout(() => {
        const dialogueNodes = quill.root.querySelectorAll('em');
        dialogueNodes.forEach(node => {
            if (node.textContent.includes(openQuote) || node.textContent.includes(closeQuote)) {
                node.style.fontFamily = style.fontFamily;
                console.log('Application directe de la police sur le nœud:', node);
            }
        });
    }, 10);
}

// Fonction utilitaire pour convertir couleur hex en RGB
function hexToRgb(hex) {
    // Supprimer le # si présent
    hex = hex.replace(/^#/, '');

    // Convertir les valeurs raccourcies (par exemple #ABC en #AABBCC)
    if (hex.length === 3) {
        hex = hex.split('').map(c => c + c).join('');
    }

    // Extraire les composantes
    const r = parseInt(hex.substring(0, 2), 16);
    const g = parseInt(hex.substring(2, 4), 16);
    const b = parseInt(hex.substring(4, 6), 16);

    return { r, g, b };
}