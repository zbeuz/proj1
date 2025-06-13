document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour rediriger vers une page quand un bouton est cliqué
    function setupButtonNavigation(buttonId, targetPage) {
        const button = document.getElementById(buttonId);
        if (button) {
            button.addEventListener('click', function() {
                window.location.href = targetPage;
            });
        }
    }

    // Configuration des boutons de navigation
    setupButtonNavigation('system-button', '../vues/accueil_formateur.php');
    setupButtonNavigation('docs-pedagogiques-button', '../vues/documents_pedagogiques.php');
    setupButtonNavigation('travail-a-faire-button', '../vues/travail_a_faire.php');
    setupButtonNavigation('depot-devoir-button', '../vues/depot_devoir.php');
    setupButtonNavigation('ajout-button', '../vues/menu_ajout.php');
    setupButtonNavigation('utilisateur-button', '../vues/liste_utilisateurs.php');
    setupButtonNavigation('fournisseur-button', '../vues/liste_fournisseurs.php');

    // Gestion des boutons dans les cartes
    const btnConsulter = document.querySelectorAll('.btn-consulter');
    btnConsulter.forEach(button => {
        button.addEventListener('click', function(event) {
            // Arrêter la propagation pour éviter de déclencher l'animation de la carte
            event.stopPropagation();
            
            // Vérifier si le bouton est dans une carte (pour les boutons hors cartes)
            const card = this.closest('.card');
            const systemId = card ? card.getAttribute('data-system-id') : null;
            const documentType = this.getAttribute('data-type');
            
            if (this.classList.contains('btn-info-fournisseur') && systemId) {
                // Redirection vers la page d'informations du fournisseur
                window.location.href = `../vues/liste_fournisseurs.php?system_id=${systemId}`;
            } else if (this.classList.contains('btn-system-supprimer') && systemId) {
                // Confirmation avant suppression
                if (confirm("Êtes-vous sûr de vouloir supprimer ce système ?")) {
                    window.location.href = `../controleurs/delete_system.php?id=${systemId}`;
                }
            } else if (systemId && documentType) {
                // Redirection vers la vue du document en fonction du type
                if (documentType == "6") {
                    // Documents pédagogiques - redirection directe
                    window.location.href = `../vues/documents_pedagogiques.php?system_id=${systemId}`;
                } else if (documentType == "1") {
                    // Documents techniques - redirection directe
                    window.location.href = `../vues/view_doc_technique.php?system_id=${systemId}`;
                } else {
                    // Autres types de documents - utiliser view_system.php
                    window.location.href = `../controleurs/view_system.php?id=${systemId}&type=${documentType}`;
                }
            }
        });
    });

    // Gestion de la recherche de fournisseurs
    const searchFournisseur = document.getElementById('searchFournisseur');
    if (searchFournisseur) {
        searchFournisseur.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            const resultsContainer = document.getElementById('searchResults');
            
            if (resultsContainer && searchTerm.length > 2) {
                // Effectuer la recherche avec AJAX
                fetch(`../controleurs/search_fournisseur.php?term=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(data => {
                        resultsContainer.innerHTML = '';
                        
                        if (data.length > 0) {
                            data.forEach(item => {
                                const resultItem = document.createElement('div');
                                resultItem.classList.add('search-result-item');
                                resultItem.textContent = item.nom_entreprise;
                                resultItem.addEventListener('click', function() {
                                    window.location.href = `../vues/liste_fournisseurs.php?fournisseur_id=${item.id_fournisseur}`;
                                });
                                resultsContainer.appendChild(resultItem);
                            });
                            resultsContainer.style.display = 'block';
                        } else {
                            resultsContainer.style.display = 'none';
                        }
                    })
                    .catch(error => console.error('Erreur lors de la recherche:', error));
            } else if (resultsContainer) {
                resultsContainer.style.display = 'none';
            }
        });
    }

    // Fermer les résultats de recherche en cliquant ailleurs
    document.addEventListener('click', function(event) {
        const searchResults = document.getElementById('searchResults');
        if (searchResults && !event.target.closest('#searchFournisseur') && !event.target.closest('#searchResults')) {
            searchResults.style.display = 'none';
        }
    });

    // Animation des cartes
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('click', function() {
            this.classList.toggle('flipped');
        });
    });
}); 