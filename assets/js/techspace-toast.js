/**
 * TechSpace Toast Notification
 * 
 * This script provides a simple, reusable toast notification function
 * for the TechSpace REST Framework plugin.
 */

const TechSpaceToast = (function() {
    'use strict';

    // Default options
    const defaults = {
        duration: 3000,
        position: 'top-right',
        className: ''
    };

    // Create and show a toast notification
    function show(message, type = 'info', options = {}) {
        const settings = {...defaults, ...options };

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `techspace-toast ${type} ${settings.className}`;
        toast.textContent = message;

        // Set position
        toast.style.position = 'fixed';
        switch (settings.position) {
            case 'top-left':
                toast.style.top = '20px';
                toast.style.left = '20px';
                break;
            case 'top-center':
                toast.style.top = '20px';
                toast.style.left = '50%';
                toast.style.transform = 'translateX(-50%)';
                break;
            case 'bottom-left':
                toast.style.bottom = '20px';
                toast.style.left = '20px';
                break;
            case 'bottom-center':
                toast.style.bottom = '20px';
                toast.style.left = '50%';
                toast.style.transform = 'translateX(-50%)';
                break;
            case 'bottom-right':
                toast.style.bottom = '20px';
                toast.style.right = '20px';
                break;
            default: // top-right
                toast.style.top = '20px';
                toast.style.right = '20px';
        }

        // Add to DOM
        document.body.appendChild(toast);

        // Trigger reflow to enable transitions
        toast.offsetHeight;

        // Show toast
        toast.style.opacity = '1';

        // Hide and remove toast after duration
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.addEventListener('transitionend', function() {
                document.body.removeChild(toast);
            });
        }, settings.duration);
    }

    // Public API
    return {
        show: show,
        success: (message, options) => show(message, 'success', options),
        error: (message, options) => show(message, 'error', options),
        warning: (message, options) => show(message, 'warning', options),
        info: (message, options) => show(message, 'info', options)
    };
})();

// Make TechSpaceToast globally available
window.TechSpaceToast = TechSpaceToast;