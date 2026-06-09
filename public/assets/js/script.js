// ============================================
// Global JavaScript
// ============================================

console.log('App initialized');

// ============================================
// Alert Helper
// ============================================

// showAlert di-handle secara global bertema Coffee Toast oleh helpers.js

// ============================================
// Form Utilities
// ============================================

function validateForm(formElement) {
    if (!formElement.checkValidity() === false) {
        return false;
    }
    return true;
}

// ============================================
// Loading State
// ============================================

function setLoadingState(element, isLoading = true) {
    if (isLoading) {
        element.disabled = true;
        element.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
    } else {
        element.disabled = false;
        element.innerHTML = 'Submit';
    }
}

// ============================================
// API Helper
// ============================================

async function apiCall(url, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };

    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(url, options);
        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'Request failed');
        }

        return result;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// ============================================
// Utility Functions
// ============================================

function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR'
    }).format(amount);
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// ============================================
// Dark Mode Toggle (if implemented)
// ============================================

function toggleDarkMode() {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-bs-theme') === 'dark';

    html.setAttribute('data-bs-theme', isDark ? 'light' : 'dark');
    localStorage.setItem('theme', isDark ? 'light' : 'dark');
}

// Initialize theme from localStorage
document.addEventListener('DOMContentLoaded', function() {
    const theme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', theme);
});

console.log('Global scripts loaded');

