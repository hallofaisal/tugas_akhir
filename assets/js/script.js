// Main JavaScript file for Sistem Informasi Akademik

document.addEventListener('DOMContentLoaded', function() {
    // Initialize the application
    initApp();
});

function initApp() {
    // Add smooth scrolling for anchor links
    addSmoothScrolling();
    
    // Add form validation
    addFormValidation();
    
    // Add interactive elements
    addInteractiveElements();
    
    // Add mobile menu toggle
    addMobileMenu();
}

function addSmoothScrolling() {
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

function addFormValidation() {
    // Form validation for login form
    const loginForm = document.querySelector('form[action="login.php"]');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const username = this.querySelector('#username').value.trim();
            const password = this.querySelector('#password').value.trim();
            const role = this.querySelector('#role').value;
            
            let isValid = true;
            let errorMessage = '';
            
            // Clear previous error messages
            clearErrors();
            
            // Validate username
            if (!username) {
                showError('username', 'Username harus diisi');
                isValid = false;
            }
            
            // Validate password
            if (!password) {
                showError('password', 'Password harus diisi');
                isValid = false;
            }
            
            // Validate role
            if (!role) {
                showError('role', 'Role harus dipilih');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
}

function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (field) {
        // Add error class to field
        field.classList.add('error');
        
        // Create error message element
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        errorDiv.style.color = '#dc3545';
        errorDiv.style.fontSize = '0.875rem';
        errorDiv.style.marginTop = '0.25rem';
        
        // Insert error message after the field
        field.parentNode.appendChild(errorDiv);
    }
}

function clearErrors() {
    // Remove error classes
    document.querySelectorAll('.error').forEach(field => {
        field.classList.remove('error');
    });
    
    // Remove error messages
    document.querySelectorAll('.field-error').forEach(error => {
        error.remove();
    });
}

function addInteractiveElements() {
    // Add hover effects for cards
    const cards = document.querySelectorAll('.feature-card, .stat-card, .action-card, .summary-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Add table row hover effects
    const tableRows = document.querySelectorAll('tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8f9fa';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
    
    // Add confirmation for logout
    const logoutLinks = document.querySelectorAll('a[href*="logout.php"]');
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Apakah Anda yakin ingin keluar?')) {
                e.preventDefault();
            }
        });
    });
}

function addMobileMenu() {
    // Create mobile menu toggle button
    const nav = document.querySelector('nav');
    if (nav) {
        const navContainer = nav.querySelector('.container');
        const navList = nav.querySelector('ul');
        
        // Create mobile menu button
        const mobileMenuBtn = document.createElement('button');
        mobileMenuBtn.className = 'mobile-menu-btn';
        mobileMenuBtn.innerHTML = '☰';
        mobileMenuBtn.style.display = 'none';
        mobileMenuBtn.style.background = 'none';
        mobileMenuBtn.style.border = 'none';
        mobileMenuBtn.style.color = 'white';
        mobileMenuBtn.style.fontSize = '1.5rem';
        mobileMenuBtn.style.cursor = 'pointer';
        
        // Add mobile menu styles
        const style = document.createElement('style');
        style.textContent = `
            @media (max-width: 768px) {
                .mobile-menu-btn {
                    display: block !important;
                }
                
                nav ul {
                    display: none;
                    flex-direction: column;
                    width: 100%;
                    margin-top: 1rem;
                }
                
                nav ul.active {
                    display: flex;
                }
                
                nav ul li {
                    margin: 0.5rem 0;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Insert mobile menu button
        navContainer.insertBefore(mobileMenuBtn, navList);
        
        // Toggle mobile menu
        mobileMenuBtn.addEventListener('click', function() {
            navList.classList.toggle('active');
        });
        
        // Hide mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!nav.contains(e.target)) {
                navList.classList.remove('active');
            }
        });
    }
}

// Utility functions
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Style the notification
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.padding = '1rem 2rem';
    notification.style.borderRadius = '5px';
    notification.style.color = 'white';
    notification.style.fontWeight = '500';
    notification.style.zIndex = '1000';
    notification.style.transform = 'translateX(100%)';
    notification.style.transition = 'transform 0.3s ease';
    
    // Set background color based on type
    switch(type) {
        case 'success':
            notification.style.backgroundColor = '#28a745';
            break;
        case 'error':
            notification.style.backgroundColor = '#dc3545';
            break;
        case 'warning':
            notification.style.backgroundColor = '#ffc107';
            notification.style.color = '#333';
            break;
        default:
            notification.style.backgroundColor = '#17a2b8';
    }
    
    // Add to page
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Hide notification after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Add loading spinner
function showLoading() {
    const spinner = document.createElement('div');
    spinner.className = 'loading-spinner';
    spinner.innerHTML = `
        <div class="spinner"></div>
        <p>Memuat...</p>
    `;
    
    // Style the spinner
    spinner.style.position = 'fixed';
    spinner.style.top = '0';
    spinner.style.left = '0';
    spinner.style.width = '100%';
    spinner.style.height = '100%';
    spinner.style.backgroundColor = 'rgba(0,0,0,0.5)';
    spinner.style.display = 'flex';
    spinner.style.flexDirection = 'column';
    spinner.style.justifyContent = 'center';
    spinner.style.alignItems = 'center';
    spinner.style.zIndex = '9999';
    spinner.style.color = 'white';
    
    const spinnerStyle = document.createElement('style');
    spinnerStyle.textContent = `
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(spinnerStyle);
    
    document.body.appendChild(spinner);
    
    return spinner;
}

function hideLoading(spinner) {
    if (spinner) {
        document.body.removeChild(spinner);
    }
}

// Export functions for global use
window.showNotification = showNotification;
window.showLoading = showLoading;
window.hideLoading = hideLoading; 