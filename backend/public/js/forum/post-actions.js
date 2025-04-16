/**
 * Script gérant les actions liées aux posts du forum DC Earth
 */

/**
 * Envoie une requête POST pour publier un post brouillon
 * @param {number} postId - L'identifiant du post à publier
 * @param {string} universeSlug - Le slug de l'univers
 */
function publishDraft(postId, universeSlug) {
    if (!postId) {
        console.error('ID du post non spécifié');
        return;
    }
    
    if (!confirm('Voulez-vous publier ce brouillon ?')) {
        return;
    }
    
    // Création du token CSRF (s'il est utilisé dans votre application)
    const tokenElement = document.querySelector('meta[name="csrf-token"]');
    // Essayez également de récupérer le token CSRF depuis le template
    const csrfToken = `publish_post_${postId}`;
    
    const headers = {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    };
    
    // Ajout du token CSRF aux en-têtes si présent
    if (tokenElement) {
        headers['X-CSRF-TOKEN'] = tokenElement.getAttribute('content');
    }
    
    // Configuration de la requête
    fetch(`/univers/${universeSlug}/post/${postId}/publish`, {
        method: 'POST',
        headers: headers,
        credentials: 'same-origin',
        body: JSON.stringify({
            '_token': document.querySelector(`input[value^='${csrfToken}']`)?.value
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        // Succès : recharger la page pour montrer le post publié
        window.location.reload();
    })
    .catch(error => {
        console.error('Erreur lors de la publication du post:', error);
        // Si l'erreur est probablement liée à l'utilisation de l'API, essayez la méthode de formulaire comme fallback
        console.log('Tentative de fallback avec la méthode de formulaire...');
        submitPublishForm(postId, universeSlug);
    });
}

/**
 * Méthode de secours qui soumet un formulaire pour publier un brouillon
 * @param {number} postId - L'identifiant du post à publier
 * @param {string} universeSlug - Le slug de l'univers
 */
function submitPublishForm(postId, universeSlug) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/univers/${universeSlug}/post/${postId}/publish`;
    
    // Essayer de récupérer le token CSRF qui pourrait être dans la page
    const possibleToken = document.querySelector(`input[value^='publish_post_${postId}']`);
    
    if (possibleToken) {
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = possibleToken.value;
        form.appendChild(csrfToken);
    }
    
    document.body.appendChild(form);
    form.submit();
}