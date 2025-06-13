// Toggle mobile menu
document.addEventListener('DOMContentLoaded', function() {
    // Handle mobile menu toggle if it exists
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            const navMenu = document.querySelector('.nav-menu');
            if (navMenu) {
                navMenu.classList.toggle('active');
            }
        });
    }
    
    // Add confirmation for delete actions
    const deleteButtons = document.querySelectorAll('.delete-btn');
    if (deleteButtons) {
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                    e.preventDefault();
                }
            });
        });
    }
    
    // Validate file uploads
    const fileInputs = document.querySelectorAll('input[type="file"]');
    if (fileInputs) {
        fileInputs.forEach(input => {
            input.addEventListener('change', function() {
                const fileSize = this.files[0].size / 1024 / 1024; // in MB
                if (fileSize > 10) {
                    alert('Le fichier est trop volumineux. Taille maximale : 10MB');
                    this.value = '';
                }
            });
        });
    }
    
    // Show password toggle
    const passwordToggles = document.querySelectorAll('.password-toggle');
    if (passwordToggles) {
        passwordToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const passwordField = document.querySelector(this.getAttribute('data-password'));
                if (passwordField) {
                    if (passwordField.type === 'password') {
                        passwordField.type = 'text';
                        this.textContent = 'Masquer';
                    } else {
                        passwordField.type = 'password';
                        this.textContent = 'Afficher';
                    }
                }
            });
        });
    }
    
    // Handle tabs in system detail page
    const tabButtons = document.querySelectorAll('.tab-button');
    if (tabButtons) {
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                tabButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Get target content and hide all content first
                const target = this.getAttribute('data-target');
                const allContents = document.querySelectorAll('.tab-content');
                allContents.forEach(content => content.style.display = 'none');
                
                // Show target content
                const targetContent = document.querySelector(target);
                if (targetContent) {
                    targetContent.style.display = 'block';
                }
            });
        });
        
        // Trigger first tab by default
        if (tabButtons.length > 0) {
            tabButtons[0].click();
        }
    }
    
    // Document filter functionality
    const filterInput = document.getElementById('document-filter');
    if (filterInput) {
        filterInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const documentItems = document.querySelectorAll('.document-item');
            
            documentItems.forEach(item => {
                const documentTitle = item.querySelector('.document-title').textContent.toLowerCase();
                if (documentTitle.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
});

// Function to confirm form submission
function confirmSubmit(message) {
    return confirm(message || 'Êtes-vous sûr de vouloir effectuer cette action ?');
}

// Function to preview image before upload
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Function to validate password match
function validatePasswordMatch(password1Id, password2Id, messageId) {
    const password1 = document.getElementById(password1Id).value;
    const password2 = document.getElementById(password2Id).value;
    const messageElement = document.getElementById(messageId);
    
    if (password1 === password2) {
        messageElement.textContent = 'Les mots de passe correspondent';
        messageElement.style.color = 'green';
        return true;
    } else {
        messageElement.textContent = 'Les mots de passe ne correspondent pas';
        messageElement.style.color = 'red';
        return false;
    }
} 