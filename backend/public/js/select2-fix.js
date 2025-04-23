/**
 * Script correctif pour Select2
 * Résout les problèmes d'initialisation multiples en détruisant proprement
 * les instances existantes avant la réinitialisation.
 */
(function() {
    // Exécuter dès que possible, avant DOMContentLoaded
    if (typeof jQuery !== 'undefined') {
        var $ = jQuery;
        
        // Méthode pour détruire proprement Select2
        $.fn.destroySelect2 = function() {
            $(this).each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });
            return this;
        };
        
        // Méthode pour réinitialiser Select2 avec des options
        $.fn.resetSelect2 = function(options) {
            return $(this).each(function() {
                $(this).destroySelect2();
                $(this).select2(options || {});
            });
        };
        
        // Nettoyer les instances existantes au démarrage
        $(document).ready(function() {
            // Attendre un court instant pour s'assurer que jQuery et Select2 sont chargés
            setTimeout(function() {
                if (typeof $.fn.select2 === 'undefined') {
                    console.error('Select2 n\'est pas chargé');
                    return;
                }
                
                // Nettoyer les événements globaux pour éviter les doublons
                $(document).off('.select2');
                
                // Étendre Select2 pour gérer les erreurs d'initialisation
                var originalSelect2 = $.fn.select2;
                $.fn.select2 = function() {
                    try {
                        var result = originalSelect2.apply(this, arguments);
                        return result;
                    } catch (e) {
                        console.warn('Erreur lors de l\'initialisation de Select2, tentative de nettoyage...', e);
                        // Si l'erreur contient "destroy is not a function", tentons de nettoyer manuellement
                        if (e.toString().indexOf('destroy is not a function') > -1) {
                            $(this).removeClass('select2-hidden-accessible');
                            $('.select2-container').remove();
                            // Nouvelle tentative après nettoyage
                            return originalSelect2.apply(this, arguments);
                        }
                        throw e;
                    }
                };
            }, 0);
        });
    }
})(); 