/**
 * ============================================================================
 * SCRIPT DE CHARGEMENT DE PAGE GLOBAL
 * ============================================================================
 * Ce script affiche un loader professionnel pendant le chargement des pages
 */

// Fonction pour afficher le loader
function showLoader() {
    const loader = document.getElementById('pageLoader');
    if (loader) {
        loader.classList.remove('hidden');
    }
}

// Fonction pour cacher le loader avec délai minimum
function hideLoader() {
    const loader = document.getElementById('pageLoader');
    if (loader) {
        // Délai minimum de 1.0 secondes pour voir l'animation
        setTimeout(function() {
            loader.classList.add('hidden');
        }, 1000); // 1000ms = 1 seconde
    }
}

// Cacher le loader dès que le DOM est chargé
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        hideLoader();
    });
} else {
    // Si le DOM est déjà chargé, cacher avec délai
    hideLoader();
}

// Aussi cacher quand tout est complètement chargé (images, etc.)
window.addEventListener('load', function() {
    hideLoader();
});

// Afficher le loader lors de la navigation vers une autre page
document.addEventListener('click', function(e) {
    const link = e.target.closest('a');
    if (link && link.href && 
        !link.target && 
        link.href.indexOf(window.location.hostname) !== -1 &&
        !link.href.includes('#') &&
        !link.hasAttribute('data-no-loader')) {
        showLoader();
    }
});

// Afficher le loader lors de la soumission de formulaires
document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form && !form.hasAttribute('data-no-loader')) {
        showLoader();
    }
});

// Afficher le loader lors de la soumission de formulaires
document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form && !form.hasAttribute('data-no-loader')) {
        showLoader();
    }
});
