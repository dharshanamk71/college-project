/**
 * College Tech Fest Management System
 * Client-Side JavaScript Helper
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Toggle
    const mobileBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');

    if (mobileBtn && navLinks) {
        mobileBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }

    // 2. Pre-select event from URL query parameter (e.g. register.html?event_id=2)
    const urlParams = new URLSearchParams(window.location.search);
    const eventIdParam = urlParams.get('event_id');
    const eventSelect = document.getElementById('event_id');

    if (eventIdParam && eventSelect) {
        // Wait briefly if options are populated asynchronously
        const selectOption = () => {
            if (eventSelect.options.length > 1) {
                eventSelect.value = eventIdParam;
            } else {
                setTimeout(selectOption, 50);
            }
        };
        selectOption();
    }
});

/**
 * Format currency to Indian Rupees / standard format
 */
function formatCurrency(amount) {
    return '₹' + parseFloat(amount).toFixed(2);
}

/**
 * Confirm deletion action in admin tables
 */
function confirmDelete(message = 'Are you sure you want to delete this record?') {
    return confirm(message);
}
