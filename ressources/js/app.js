document.addEventListener('DOMContentLoaded', function() {
    // Gestion des boutons de la sidebar
    document.getElementById('systeme-button').addEventListener('click', function() {
        // Rechargement de la page pour afficher les systèmes
        window.location.href = '../vues/accueil_apprenti.php';
    });

    document.getElementById('docs-pedagogiques-button').addEventListener('click', function() {
        // Redirection vers la page des documents pédagogiques
        window.location.href = '../vues/documents_pedagogiques_apprenti.php';
    });

    document.getElementById('travail-a-faire-button').addEventListener('click', function() {
        // Redirection vers la page des travaux à faire
        window.location.href = '../vues/travail_a_faire_apprenti.php';
    });

    // Gestion des boutons dans les cartes
    const btnTechDoc = document.querySelectorAll('.btn-tech-doc');
    btnTechDoc.forEach(button => {
        button.addEventListener('click', function(event) {
            event.stopPropagation(); // Empêcher le basculement de la carte
            const systemId = this.closest('.card').getAttribute('data-system-id');
            const documentType = this.getAttribute('data-type');
            
            // Redirection vers la vue du document technique
            window.location.href = `../vues/view_doc_technique.php?system_id=${systemId}`;
        });
    });

    const btnTravailAFaire = document.querySelectorAll('.btn-travail-a-faire');
    btnTravailAFaire.forEach(button => {
        button.addEventListener('click', function(event) {
            event.stopPropagation(); // Empêcher le basculement de la carte
            const systemId = this.closest('.card').getAttribute('data-system-id');
            
            // Redirection vers la vue des travaux à faire pour ce système
            window.location.href = `../vues/travail_a_faire_apprenti.php?id=${systemId}`;
        });
    });

    const btnDocumentsPedagogiques = document.querySelectorAll('.btn-documents-pedagogiques');
    btnDocumentsPedagogiques.forEach(button => {
        button.addEventListener('click', function(event) {
            event.stopPropagation(); // Empêcher le basculement de la carte
            const systemId = this.closest('.card').getAttribute('data-system-id');
            
            // Redirection vers la vue des documents pédagogiques pour ce système
            window.location.href = `../vues/documents_pedagogiques_apprenti.php?system_id=${systemId}`;
        });
    });

    // Animation des cartes
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('click', function() {
            this.classList.toggle('flipped');
        });
    });

    // Gestion de la recherche
    const searchBtn = document.querySelector('.search-btn');
    const searchInput = document.querySelector('.search-bar input');
    
    searchBtn.addEventListener('click', function() {
        const searchTerm = searchInput.value.trim();
        if (searchTerm.length > 0) {
            // Effectuer la recherche
            window.location.href = `../controleurs/search_documents.php?term=${encodeURIComponent(searchTerm)}`;
        }
    });

    // Permettre la recherche en appuyant sur Entrée
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchBtn.click();
        }
    });
}); 