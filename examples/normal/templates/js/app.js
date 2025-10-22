// Custom JavaScript for CommentTemplate example

document.addEventListener('DOMContentLoaded', function() {
    console.log('CommentTemplate example loaded successfully!');
    
    // Add some interactive functionality
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Add click handlers to list items
    const listItems = document.querySelectorAll('.list-group-item');
    listItems.forEach(item => {
        item.addEventListener('click', function() {
            this.classList.toggle('active');
        });
    });
});