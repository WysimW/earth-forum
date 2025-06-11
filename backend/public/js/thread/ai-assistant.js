document.addEventListener('DOMContentLoaded', function() {
    const aiAssistant = document.querySelector('.ai-assistant');
    if (!aiAssistant) return;

    const personaSelect = document.getElementById('ai-persona');
    const contextTextarea = document.getElementById('ai-context');
    const generateButton = document.getElementById('generate-ai-response');
    const toggleButton = aiAssistant.querySelector('.ai-assistant__toggle');
    const content = aiAssistant.querySelector('.ai-assistant__content');

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
            
            // Insérer la réponse générée dans l'éditeur
            const editor = document.querySelector('.tox-tinymce');
            if (editor && editor.editor) {
                editor.editor.setContent(result.response);
            } else {
                const textarea = document.querySelector('textarea[name="content"]');
                if (textarea) {
                    textarea.value = result.response;
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
}); 