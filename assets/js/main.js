// Loading Spinner
const LoadingSpinner = {
    show() {
        const spinner = document.getElementById('loading-spinner');
        if (!spinner) {
            const container = document.createElement('div');
            container.id = 'loading-spinner';
            container.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center';
            container.style.background = 'rgba(0,0,0,0.5)';
            container.style.zIndex = '9999';
            
            container.innerHTML = `
                <div class="spinner-border text-light" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            `;
            
            document.body.appendChild(container);
        } else {
            spinner.classList.remove('d-none');
        }
    },
    
    hide() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.classList.add('d-none');
        }
    }
};

// Toast Notifications
const Toast = {
    show(message, type = 'success') {
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        document.getElementById('toast-container').appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();

        toast.addEventListener('hidden.bs.toast', function () {
            toast.remove();
        });
    }
};

// Form Validation
const FormValidator = {
    validate(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('is-invalid');
                
                // Add error message if not exists
                if (!input.nextElementSibling?.classList.contains('invalid-feedback')) {
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = 'This field is required';
                    input.parentNode.insertBefore(feedback, input.nextSibling);
                }
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        return isValid;
    },
    
    reset(form) {
        const inputs = form.querySelectorAll('.is-invalid');
        inputs.forEach(input => {
            input.classList.remove('is-invalid');
            const feedback = input.nextElementSibling;
            if (feedback?.classList.contains('invalid-feedback')) {
                feedback.remove();
            }
        });
    }
};

// AJAX Helper
const Ajax = {
    async post(url, data) {
        try {
            LoadingSpinner.show();
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            LoadingSpinner.hide();
            return result;
        } catch (error) {
            LoadingSpinner.hide();
            Toast.show('An error occurred. Please try again.', 'danger');
            throw error;
        }
    },
    
    async get(url) {
        try {
            LoadingSpinner.show();
            const response = await fetch(url);
            const result = await response.json();
            LoadingSpinner.hide();
            return result;
        } catch (error) {
            LoadingSpinner.hide();
            Toast.show('An error occurred. Please try again.', 'danger');
            throw error;
        }
    }
};

// Utility Functions
const Utils = {
    // Format date to local string
    formatDate(date) {
        return new Date(date).toLocaleDateString();
    },
    
    // Format currency
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    },
    
    // Debounce function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // Generate random ID
    generateId() {
        return Math.random().toString(36).substr(2, 9);
    }
};

// Initialize common functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Add smooth scrolling to all links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Add form validation to all forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!FormValidator.validate(this)) {
                e.preventDefault();
            }
        });
    });
}); 