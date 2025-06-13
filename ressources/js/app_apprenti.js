document.addEventListener('DOMContentLoaded', function() {
    // Gestion des boutons de la sidebar
    const systemeButton = document.getElementById('system-button');
    if (systemeButton) {
        systemeButton.addEventListener('click', function() {
            window.location.href = '../vues/accueil_apprenti.php';
        });
    }

    const docsPedagogiquesButton = document.getElementById('docs-pedagogiques-button');
    if (docsPedagogiquesButton) {
        docsPedagogiquesButton.addEventListener('click', function() {
            window.location.href = '../vues/documents_pedagogiques_apprenti.php';
        });
    }

    const travailAFaireButton = document.getElementById('travail-a-faire-button');
    if (travailAFaireButton) {
        travailAFaireButton.addEventListener('click', function() {
            window.location.href = '../vues/travail_a_faire_apprenti.php';
        });
    }

    // Gestion des messages
    const successMessage = document.querySelector('.success-message');
    const errorMessage = document.querySelector('.error-message');
    
    if (successMessage || errorMessage) {
        setTimeout(function() {
            if (successMessage) successMessage.style.display = 'none';
            if (errorMessage) errorMessage.style.display = 'none';
        }, 5000);
    }

    // Gestion de la recherche
    const searchBtn = document.querySelector('.search-btn');
    const searchInput = document.querySelector('.search-bar input');
    
    if (searchBtn && searchInput) {
        searchBtn.addEventListener('click', function() {
            const searchTerm = searchInput.value.trim();
            if (searchTerm.length > 0) {
                window.location.href = `../controleurs/search_documents_apprenti.php?term=${encodeURIComponent(searchTerm)}`;
            }
        });
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchBtn.click();
            }
        });
    }

    // Fonction pour mettre en surbrillance le bouton actif
    function setActiveButton() {
        const currentPage = window.location.pathname.split('/').pop();
        
        // Réinitialiser tous les boutons
        const buttons = document.querySelectorAll('.sidebar-btn');
        buttons.forEach(btn => btn.classList.remove('active'));
        
        // Définir le bouton actif en fonction de la page actuelle
        if (currentPage.includes('systeme')) {
            systemeButton?.classList.add('active');
        } else if (currentPage.includes('doc_pedagogique')) {
            docsPedagogiquesButton?.classList.add('active');
        } else if (currentPage.includes('travail_a_faire')) {
            travailAFaireButton?.classList.add('active');
        }
    }

    // Appeler la fonction au chargement de la page
    setActiveButton();

    // Gestion des accordéons si présents sur la page
    const accordionItems = document.querySelectorAll('.accordion-item');
    if (accordionItems.length > 0) {
        accordionItems.forEach(item => {
            const header = item.querySelector('.accordion-header');
            const content = item.querySelector('.accordion-content');
            
            header.addEventListener('click', () => {
                // Fermer tous les autres accordéons
                accordionItems.forEach(otherItem => {
                    if (otherItem !== item) {
                        otherItem.querySelector('.accordion-content').style.maxHeight = null;
                        otherItem.classList.remove('active');
                    }
                });
                
                // Basculer l'état de l'accordéon actuel
                item.classList.toggle('active');
                
                if (item.classList.contains('active')) {
                    content.style.maxHeight = content.scrollHeight + 'px';
                } else {
                    content.style.maxHeight = null;
                }
            });
        });
    }

    // Gestion des filtres si présents sur la page
    const filterForm = document.querySelector('.filter-form');
    if (filterForm) {
        filterForm.addEventListener('change', function() {
            this.submit();
        });
    }

    // Animations pour les cartes de travaux
    const travailCards = document.querySelectorAll('.travail-card');
    if (travailCards.length > 0) {
        travailCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px)';
                card.style.boxShadow = '0 10px 15px rgba(0,0,0,0.1)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
                card.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
            });
        });
    }
}); 