// Analytics script example - loaded asynchronously
(function() {
    'use strict';
    
    // Simulate analytics tracking
    console.log('Analytics loaded asynchronously');
    
    // Track page view
    setTimeout(function() {
        console.log('Page view tracked');
    }, 1000);
    
    // Track user interactions
    document.addEventListener('click', function(e) {
        if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON') {
            console.log('User interaction tracked:', e.target.textContent);
        }
    });
})();