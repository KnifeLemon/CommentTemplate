// Utility functions
const utils = {
    debounce: function(func, wait) {
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
    
    formatDate: function(date) {
        return new Date(date).toLocaleDateString();
    },
    
    generateId: function() {
        return '_' + Math.random().toString(36).substr(2, 9);
    }
};
