/**
 * URL Router for removing .html extensions from URLs
 * This script handles client-side routing to hide .html extensions
 */

(function() {
    'use strict';

    // Intercept all link clicks
    document.addEventListener('DOMContentLoaded', function() {
        // Handle all anchor clicks
        document.addEventListener('click', function(e) {
            const target = e.target.closest('a');

            if (!target) return;

            const href = target.getAttribute('href');

            // Skip if external link, anchor, javascript, or already handled
            if (!href ||
                href.startsWith('http') ||
                href.startsWith('//') ||
                href.startsWith('#') ||
                href.startsWith('javascript:') ||
                href.startsWith('mailto:') ||
                href.startsWith('tel:') ||
                target.getAttribute('target') === '_blank') {
                return;
            }

            // Skip API calls
            if (href.includes('/api/')) {
                return;
            }

            // If link has .html, prevent default and navigate without it
            if (href.includes('.html')) {
                e.preventDefault();

                // Remove .html from URL
                const cleanUrl = href.replace(/\.html(\?|#|$)/, '$1');

                // Use History API to change URL without page reload
                window.history.pushState({}, '', cleanUrl);

                // Load the actual .html file
                window.location.href = href;
            }
        });

        // Update current page URL if it has .html
        if (window.location.pathname.includes('.html')) {
            const cleanUrl = window.location.href.replace(/\.html(\?|#|$)/, '$1');
            window.history.replaceState({}, '', cleanUrl);
        }
    });
})();
