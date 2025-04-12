/**
 * Career Roadmap Generator - Main JavaScript
 * Common functionality and utilities
 */

// Form validation utility
const FormValidator = {
    validateRequired: function(value, fieldName) {
        if (!value || value.trim() === '') {
            throw new Error(`${fieldName} is required`);
        }
        return value.trim();
    },

    validateEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            throw new Error('Please enter a valid email address');
        }
        return email;
    },

    validatePassword: function(password) {
        if (password.length < 8) {
            throw new Error('Password must be at least 8 characters long');
        }
        return password;
    },

    validateDate: function(date) {
        const dateObj = new Date(date);
        if (isNaN(dateObj.getTime())) {
            throw new Error('Please enter a valid date');
        }
        return date;
    }
};

// AJAX utility functions
const API = {
    post: async function(url, data) {
        showLoading();
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error:', error);
            showToast(error.message, 'danger');
            throw error;
        } finally {
            hideLoading();
        }
    },

    get: async function(url, params = {}) {
        showLoading();
        try {
            const queryString = new URLSearchParams(params).toString();
            const fullUrl = queryString ? `${url}?${queryString}` : url;
            
            const response = await fetch(fullUrl);
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error:', error);
            showToast(error.message, 'danger');
            throw error;
        } finally {
            hideLoading();
        }
    }
};

// Chart utility functions
const ChartUtils = {
    colors: {
        primary: getComputedStyle(document.documentElement).getPropertyValue('--primary-color').trim(),
        secondary: getComputedStyle(document.documentElement).getPropertyValue('--secondary-color').trim(),
        accent: getComputedStyle(document.documentElement).getPropertyValue('--accent-color').trim(),
        danger: getComputedStyle(document.documentElement).getPropertyValue('--danger-color').trim(),
        gray: getComputedStyle(document.documentElement).getPropertyValue('--gray-500').trim()
    },

    createProgressChart: function(canvasId, data) {
        return new Chart(document.getElementById(canvasId), {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: [
                        this.colors.primary,
                        this.colors.secondary,
                        this.colors.accent,
                        this.colors.danger,
                        this.colors.gray
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    },

    createSkillChart: function(canvasId, data) {
        return new Chart(document.getElementById(canvasId), {
            type: 'radar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Current Level',
                    data: data.values,
                    backgroundColor: `${this.colors.primary}33`,
                    borderColor: this.colors.primary,
                    pointBackgroundColor: this.colors.primary
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 3,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
};

// Animation utility functions
const AnimationUtils = {
    animate: function(element, animation, duration = 1000) {
        return new Promise((resolve, reject) => {
            element.style.animation = `${animation} ${duration}ms`;
            
            const handleAnimationEnd = () => {
                element.style.animation = '';
                element.removeEventListener('animationend', handleAnimationEnd);
                resolve();
            };
            
            element.addEventListener('animationend', handleAnimationEnd);
        });
    },

    fadeIn: async function(element, duration = 500) {
        element.style.opacity = '0';
        element.style.display = 'block';
        
        await new Promise(resolve => setTimeout(resolve, 10));
        
        element.style.transition = `opacity ${duration}ms`;
        element.style.opacity = '1';
        
        return new Promise(resolve => {
            setTimeout(() => {
                element.style.transition = '';
                resolve();
            }, duration);
        });
    },

    fadeOut: async function(element, duration = 500) {
        element.style.transition = `opacity ${duration}ms`;
        element.style.opacity = '0';
        
        return new Promise(resolve => {
            setTimeout(() => {
                element.style.display = 'none';
                element.style.transition = '';
                element.style.opacity = '';
                resolve();
            }, duration);
        });
    }
};

// Date utility functions
const DateUtils = {
    formatDate: function(date) {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    },

    formatDateTime: function(date) {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    getRelativeTime: function(date) {
        const rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });
        const now = new Date();
        const diff = new Date(date) - now;
        
        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);
        
        if (Math.abs(days) > 0) return rtf.format(days, 'day');
        if (Math.abs(hours) > 0) return rtf.format(hours, 'hour');
        if (Math.abs(minutes) > 0) return rtf.format(minutes, 'minute');
        return rtf.format(seconds, 'second');
    }
};

// String utility functions
const StringUtils = {
    truncate: function(str, length = 100) {
        if (str.length <= length) return str;
        return str.substring(0, length) + '...';
    },

    capitalize: function(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    },

    slugify: function(str) {
        return str
            .toLowerCase()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
};

// Initialize common functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap components
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Add form validation to all forms with 'needs-validation' class
    const forms = document.querySelectorAll('form.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Add smooth scrolling to all links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href'))?.scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
});

// Export utilities for use in other scripts
window.FormValidator = FormValidator;
window.API = API;
window.ChartUtils = ChartUtils;
window.AnimationUtils = AnimationUtils;
window.DateUtils = DateUtils;
window.StringUtils = StringUtils; 