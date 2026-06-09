/**
 * Global UI Helpers
 * Used across all views
 */

// Inject Coffee Toast CSS styles dynamically
(function injectCoffeeToastStyles() {
    if (typeof document === 'undefined') return;
    const styleId = 'coffee-toast-styles';
    if (document.getElementById(styleId)) return;

    const styleEl = document.createElement('style');
    styleEl.id = styleId;
    styleEl.innerHTML = `
        .coffee-toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-width: 380px;
            width: calc(100% - 48px);
        }
        .coffee-toast {
            background: rgba(30, 20, 15, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-left: 5px solid #8B5A2B;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            color: #F5EBE6;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            transform: translateX(120%);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s;
            opacity: 0;
            position: relative;
            overflow: hidden;
        }
        .coffee-toast.show {
            transform: translateX(0);
            opacity: 1;
        }
        .coffee-toast.hide {
            transform: translateX(120%);
            opacity: 0;
        }
        .coffee-toast-title {
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            margin-bottom: 2px;
            color: #E6C280;
        }
        .coffee-toast-message {
            font-family: 'Inter', sans-serif;
            font-size: 0.85rem;
            opacity: 0.9;
            line-height: 1.4;
        }
        .coffee-toast-close {
            background: none;
            border: none;
            color: #F5EBE6;
            opacity: 0.5;
            cursor: pointer;
            font-size: 1rem;
            padding: 0;
            margin-left: auto;
            transition: opacity 0.2s;
        }
        .coffee-toast-close:hover {
            opacity: 1;
        }
        .coffee-toast-icon {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .coffee-toast-success {
            border-left-color: #2ECC71;
        }
        .coffee-toast-success .coffee-toast-title {
            color: #A2E8B9;
        }
        .coffee-toast-danger {
            border-left-color: #E74C3C;
        }
        .coffee-toast-danger .coffee-toast-title {
            color: #F19E95;
        }
        .coffee-toast-warning {
            border-left-color: #F1C40F;
        }
        .coffee-toast-warning .coffee-toast-title {
            color: #F9E79F;
        }
        .coffee-toast-info {
            border-left-color: #E6C280;
        }
        .coffee-toast-info .coffee-toast-title {
            color: #F5EBE6;
        }
    `;
    document.head.appendChild(styleEl);
})();

/**
 * Show coffee-themed toast notification
 */
function showAlert(message, type = 'info') {
    // Ensure container exists
    let container = document.querySelector('.coffee-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'coffee-toast-container';
        document.body.appendChild(container);
    }

    // Determine Coffee themed Title and Icon based on type
    let title = '📋 Catatan Barista';
    let icon = 'fas fa-mug-hot text-info';
    let typeClass = 'coffee-toast-info';

    switch (type) {
        case 'success':
            title = '☕ Espresso Sempurna!';
            icon = 'fas fa-coffee text-success';
            typeClass = 'coffee-toast-success';
            break;
        case 'danger':
        case 'error':
            title = '⚠️ Kopi Tumpah!';
            icon = 'fas fa-exclamation-triangle text-danger';
            typeClass = 'coffee-toast-danger';
            break;
        case 'warning':
            title = '🔥 Roast Terlalu Panas!';
            icon = 'fas fa-fire text-warning';
            typeClass = 'coffee-toast-warning';
            break;
        case 'info':
            title = '🎙️ Catatan Barista';
            icon = 'fas fa-mug-hot text-info';
            typeClass = 'coffee-toast-info';
            break;
    }

    // Create Toast element
    const toast = document.createElement('div');
    toast.className = `coffee-toast ${typeClass}`;
    toast.innerHTML = `
        <div class="coffee-toast-icon">
            <i class="${icon}"></i>
        </div>
        <div class="flex-grow-1">
            <div class="coffee-toast-title">${title}</div>
            <div class="coffee-toast-message">${message}</div>
        </div>
        <button class="coffee-toast-close">
            <i class="fas fa-times"></i>
        </button>
    `;

    container.appendChild(toast);

    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 50);

    // Close on button click
    const closeBtn = toast.querySelector('.coffee-toast-close');
    closeBtn.addEventListener('click', () => {
        toast.classList.remove('show');
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 400);
    });

    // Auto close after 4 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.classList.remove('show');
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 400);
        }
    }, 4000);
}

/**
 * Format currency to Rupiah
 */
function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
}

/**
 * Format time duration
 */
function formatDuration(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${String(secs).padStart(2, '0')}`;
}

/**
 * Show loading spinner
 */
function showLoader(text = 'Loading...') {
    const loader = `
        <div class="text-center py-5">
            <div class="spinner-border text-danger" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">${text}</p>
        </div>
    `;
    return loader;
}

/**
 * Validate email
 */
function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Validate password strength
 */
function isStrongPassword(password) {
    return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(password);
}

/**
 * Get query parameter
 */
function getQueryParam(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
}

/**
 * Handle API errors globally
 */
function handleApiError(error) {
    if (error.response?.status === 401) {
        window.location.href = '/auth/login';
    } else if (error.response?.status === 403) {
        showAlert('Unauthorized access', 'danger');
    } else if (error.response?.status === 429) {
        showAlert('Too many requests. Please try again later.', 'warning');
    } else {
        showAlert(error.message || 'An error occurred', 'danger');
    }
}

/**
 * Copy to clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showAlert('Copied to clipboard!', 'success');
    }).catch(() => {
        showAlert('Failed to copy', 'danger');
    });
}

console.log('Global helpers loaded');

