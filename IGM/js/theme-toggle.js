/**
 * IGM Theme Toggle
 * Handles light/dark theme switching with localStorage persistence
 */

// Initialize theme on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeTheme();
    setupThemeToggle();
});

/**
 * Initialize theme from localStorage
 */
function initializeTheme() {
    const savedTheme = localStorage.getItem('igm-theme') || 'light';
    applyTheme(savedTheme);
}

/**
 * Apply theme to document
 * @param {string} theme - 'light' or 'dark'
 */
function applyTheme(theme) {
    const body = document.body;
    const themeToggle = document.getElementById('themeToggle');
    
    if (theme === 'dark') {
        body.classList.add('dark-theme');
        if (themeToggle) {
            themeToggle.innerHTML = '<i class="fas fa-sun"></i><span id="themeLabel">Light</span>';
            themeToggle.setAttribute('aria-label', 'Switch to Light Theme');
        }
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        body.classList.remove('dark-theme');
        if (themeToggle) {
            themeToggle.innerHTML = '<i class="fas fa-moon"></i><span id="themeLabel">Dark</span>';
            themeToggle.setAttribute('aria-label', 'Switch to Dark Theme');
        }
        document.documentElement.setAttribute('data-theme', 'light');
    }
}

/**
 * Setup theme toggle button
 */
function setupThemeToggle() {
    const themeToggle = document.getElementById('themeToggle');
    
    if (!themeToggle) return;
    
    themeToggle.addEventListener('click', function() {
        const currentTheme = localStorage.getItem('igm-theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        applyTheme(newTheme);
        localStorage.setItem('igm-theme', newTheme);
        
        // Optional: Trigger custom event for other components
        const event = new CustomEvent('themeChanged', { detail: { theme: newTheme } });
        document.dispatchEvent(event);
    });
}

/**
 * Get current theme
 * @returns {string} 'light' or 'dark'
 */
function getCurrentTheme() {
    return localStorage.getItem('igm-theme') || 'light';
}

/**
 * Set theme programmatically
 * @param {string} theme - 'light' or 'dark'
 */
function setTheme(theme) {
    if (['light', 'dark'].includes(theme)) {
        applyTheme(theme);
        localStorage.setItem('igm-theme', theme);
    }
}
