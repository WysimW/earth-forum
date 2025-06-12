document.addEventListener('DOMContentLoaded', function() {
    const aiAssistant = document.querySelector('.ai-assistant');
    if (!aiAssistant) return;

    const personaSelect = document.getElementById('ai-persona');
    const contextTextarea = document.getElementById('ai-context');
    const generateButton = document.getElementById('generate-ai-response');
    const toggleButton = aiAssistant.querySelector('.ai-assistant__toggle');
    const content = aiAssistant.querySelector('.ai-assistant__content');
    const responseEditor = document.getElementById('ai-response-editor');
    const publishButton = document.getElementById('publish-ai-response');
    const draftButton = document.getElementById('save-draft-ai-response');
    const regenerateButton = document.getElementById('regenerate-ai-response');

    // Charger la liste des personas
    fetch('/ai-persona/list')
        .then(response => response.json())
        .then(personas => {
            personas.forEach(persona => {
                const option = document.createElement('option');
                option.value = persona.id;
                option.textContent = `${persona.character.name} (${persona.character.race} ${persona.character.class})`;
                personaSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Erreur lors du chargement des personas:', error));

    // Gérer le toggle de l'assistant
    toggleButton.addEventListener('click', () => {
        content.style.display = content.style.display === 'none' ? 'block' : 'none';
        toggleButton.setAttribute('aria-expanded', content.style.display === 'block');
    });

    // Activer/désactiver le bouton de génération
    personaSelect.addEventListener('change', () => {
        generateButton.disabled = !personaSelect.value;
    });

    // Gérer la génération de réponse
    generateButton.addEventListener('click', async () => {
        if (!personaSelect.value) return;

        const postId = window.location.pathname.split('/').pop();
        const data = {
            personaId: personaSelect.value,
            additionalContext: contextTextarea.value
        };

        generateButton.disabled = true;
        generateButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Génération...';

        try {
            const response = await fetch(`/ai-persona/generate-response/${postId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error('Erreur lors de la génération de la réponse');
            }

            const result = await response.json();
            
            // Afficher la réponse dans l'éditeur AI
            const aiResponseContent = document.getElementById('ai-response-content');
            if (aiResponseContent) {
                aiResponseContent.innerHTML = result.response;
                responseEditor.classList.add('show');
                
                // Activer les boutons d'action
                publishButton.disabled = false;
                draftButton.disabled = false;
                regenerateButton.disabled = false;
                
                // Afficher le nom du persona utilisé
                const personaInfo = document.getElementById('ai-response-persona-info');
                if (personaInfo) {
                    const selectedOption = personaSelect.options[personaSelect.selectedIndex];
                    personaInfo.textContent = `Par ${selectedOption.textContent}`;
                }
            }

            // Réinitialiser l'interface
            contextTextarea.value = '';
            generateButton.innerHTML = '<i class="fas fa-magic"></i> Générer une réponse';
            generateButton.disabled = false;

        } catch (error) {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de la génération de la réponse.');
            generateButton.innerHTML = '<i class="fas fa-magic"></i> Générer une réponse';
            generateButton.disabled = false;
        }
    });

    // Gérer la publication de la réponse
    if (publishButton) {
        publishButton.addEventListener('click', async () => {
            await publishAIResponse(false); // false = publier directement
        });
    }

    // Gérer la sauvegarde en brouillon
    if (draftButton) {
        draftButton.addEventListener('click', async () => {
            await publishAIResponse(true); // true = sauvegarder en brouillon
        });
    }

    // Gérer la régénération
    if (regenerateButton) {
        regenerateButton.addEventListener('click', () => {
            // Relancer la génération avec les mêmes paramètres
            generateButton.click();
        });
    }

    // Fonction pour publier ou sauvegarder la réponse IA
    async function publishAIResponse(isDraft) {
        const aiResponseContent = document.getElementById('ai-response-content');
        if (!aiResponseContent || !personaSelect.value) return;

        const content = aiResponseContent.innerHTML;
        const threadId = window.location.pathname.split('/').pop();
        
        const button = isDraft ? draftButton : publishButton;
        const originalText = button.innerHTML;
        
        button.disabled = true;
        button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${isDraft ? 'Sauvegarde...' : 'Publication...'}`;

        try {
            const response = await fetch(`/ai-persona/publish-response/${threadId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    personaId: personaSelect.value,
                    content: content,
                    isDraft: isDraft
                })
            });

            if (!response.ok) {
                throw new Error(`Erreur lors de la ${isDraft ? 'sauvegarde' : 'publication'}`);
            }

            const result = await response.json();
            
            if (result.success) {
                // Rediriger vers le thread ou afficher un message de succès
                if (!isDraft) {
                    window.location.reload(); // Recharger pour voir le nouveau post
                } else {
                    alert('Réponse sauvegardée en brouillon avec succès !');
                    
                    // Masquer l'éditeur de réponse
                    responseEditor.classList.remove('show');
                    
                    // Réinitialiser les boutons
                    publishButton.disabled = true;
                    draftButton.disabled = true;
                    regenerateButton.disabled = true;
                }
            } else {
                throw new Error(result.error || `Erreur lors de la ${isDraft ? 'sauvegarde' : 'publication'}`);
            }

        } catch (error) {
            console.error('Erreur:', error);
            alert(`Une erreur est survenue lors de la ${isDraft ? 'sauvegarde' : 'publication'} de la réponse.`);
        } finally {
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }
}); 