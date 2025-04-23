/**
 * Script d'initialisation pour Select2
 * Garantit que Select2 est correctement initialisé sur tous les éléments avec la classe .select2
 */
function initSelect2() {
    if (typeof jQuery === 'undefined') {
        console.error('Erreur: jQuery n\'est pas chargé!');
        return false;
    }
    
    if (typeof jQuery.fn.select2 === 'undefined') {
        console.error('Erreur: Select2 n\'est pas chargé!');
        return false;
    }

    // Sélectionner tous les selects avec la classe .select2 qui ne sont pas déjà initialisés
    var $selects = jQuery('.select2:not(.select2-hidden-accessible)');
    
    if ($selects.length > 0) {
        $selects.each(function() {
            var $select = jQuery(this);
            
            // Configurer les options en fonction des attributs data-*
            var options = {
                theme: 'bootstrap-5',
                width: '100%'
            };
            
            // Ajouter le placeholder si défini
            if ($select.data('placeholder')) {
                options.placeholder = $select.data('placeholder');
                options.allowClear = true;
            }
            
            try {
                $select.select2(options);
                console.debug('Select2 initialisé sur', $select);
                
                // Correction du problème d'accessibilité avec aria-hidden
                // Événements liés à l'ouverture/fermeture du dropdown
                $select.on('select2:open', function() {
                    // Quand le dropdown s'ouvre, on s'assure que le container n'est pas caché pour l'accessibilité
                    setTimeout(function() {
                        var container = jQuery('.select2-container--open').removeAttr('aria-hidden');
                        // S'assurer que le champ de recherche est correctement accessible
                        var searchField = jQuery('.select2-search__field');
                        if (searchField.length) {
                            searchField.attr('aria-label', 'Rechercher');
                        }
                    }, 0);
                });
                
                // Événement lié au focus dans le champ de recherche
                jQuery(document).on('focus', '.select2-search__field', function() {
                    // Quand un élément obtient le focus, on retire aria-hidden de son parent Select2
                    jQuery(this).closest('.select2-container').removeAttr('aria-hidden');
                });
                
            } catch (error) {
                console.error('Erreur lors de l\'initialisation de Select2:', error);
            }
        });
        
        console.info('Select2 initialisé sur', $selects.length, 'éléments');
        return true;
    } else {
        console.info('Aucun élément Select2 à initialiser trouvé');
        return false;
    }
}

// Initialiser Select2 au chargement du document
document.addEventListener('DOMContentLoaded', function() {
    initSelect2();
    
    // Réinitialiser Select2 après toute mise à jour du DOM via AJAX
    jQuery(document).on('ajaxComplete', function() {
        setTimeout(function() {
            initSelect2();
        }, 100);
    });
});