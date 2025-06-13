// Document search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('search-form');
    const searchInput = document.getElementById('search-input');
    const searchResults = document.getElementById('search-results');
    const loadingIndicator = document.getElementById('loading-indicator');
    
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
    }
    
    if (searchInput) {
        // Auto-search after delay when typing
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 500); // 500ms delay
        });
    }
    
    function performSearch() {
        const query = searchInput.value.trim();
        
        if (query.length < 2) {
            // Clear results if query is too short
            if (searchResults) {
                searchResults.innerHTML = '<p class="no-results">Veuillez saisir au moins 2 caractères</p>';
            }
            return;
        }
        
        // Show loading indicator
        if (loadingIndicator) {
            loadingIndicator.style.display = 'block';
        }
        
        // Perform AJAX request
        fetch(`../controleurs/ajax_search_documents.php?query=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                // Hide loading indicator
                if (loadingIndicator) {
                    loadingIndicator.style.display = 'none';
                }
                
                if (data.error) {
                    if (searchResults) {
                        searchResults.innerHTML = `<p class="error">${data.error}</p>`;
                    }
                    return;
                }
                
                if (data.results && data.results.length > 0) {
                    displayResults(data.results);
                } else {
                    if (searchResults) {
                        searchResults.innerHTML = '<p class="no-results">Aucun résultat trouvé</p>';
                    }
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                if (loadingIndicator) {
                    loadingIndicator.style.display = 'none';
                }
                if (searchResults) {
                    searchResults.innerHTML = '<p class="error">Erreur lors de la recherche</p>';
                }
            });
    }
    
    function displayResults(results) {
        if (!searchResults) return;
        
        let html = '<div class="search-results-container">';
        
        html += `<p class="results-count">${results.length} résultat(s) trouvé(s)</p>`;
        
        // Group results by system
        const groupedResults = {};
        results.forEach(doc => {
            if (!groupedResults[doc.system]) {
                groupedResults[doc.system] = [];
            }
            groupedResults[doc.system].push(doc);
        });
        
        // Display results by system
        for (const system in groupedResults) {
            html += `<div class="system-group">`;
            html += `<h3 class="system-name">${system}</h3>`;
            html += `<div class="documents-list">`;
            
            groupedResults[system].forEach(doc => {
                const docType = doc.type === 'technique' ? 'Technique' : 'Pédagogique';
                const typeClass = doc.type === 'technique' ? 'tech-doc' : 'peda-doc';
                
                html += `<div class="document-item ${typeClass}">`;
                html += `<div class="document-icon"><img src="../ressources/images/${doc.type === 'technique' ? 'doc_tech.png' : 'doc_peda.png'}" alt="${docType}"></div>`;
                html += `<div class="document-details">`;
                html += `<h4 class="document-title">${doc.title}</h4>`;
                html += `<p class="document-meta">Type: ${docType} | Catégorie: ${doc.category} | Date: ${doc.date}</p>`;
                html += `<p class="document-author">Auteur: ${doc.author}</p>`;
                html += `</div>`;
                html += `<div class="document-actions">`;
                html += `<a href="../controleurs/download_document.php?id=${doc.id}&type=${doc.type}&system_id=${doc.system_id}" class="btn btn-download">Télécharger</a>`;
                html += `<a href="../vues/view_doc_technique.php?system_id=${doc.system_id}" class="btn btn-view">Voir le système</a>`;
                html += `</div>`;
                html += `</div>`;
            });
            
            html += `</div>`;
            html += `</div>`;
        }
        
        html += '</div>';
        searchResults.innerHTML = html;
    }
}); 