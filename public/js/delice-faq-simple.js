/**
 * Simple FAQ Toggle - Clean Implementation
 */
(function() {
    'use strict';
    
    function initFAQ() {
        const faqButtons = document.querySelectorAll('.delice-recipe-modern-faq-question');
        
        faqButtons.forEach(button => {
            button.addEventListener('click', function() {
                const faqItem = this.closest('.delice-recipe-modern-faq-item');
                const isOpen = faqItem.classList.contains('faq-open');
                
                // Close all FAQs
                document.querySelectorAll('.delice-recipe-modern-faq-item').forEach(item => {
                    item.classList.remove('faq-open');
                });
                
                // Toggle this one
                if (!isOpen) {
                    faqItem.classList.add('faq-open');
                }
            });
        });
    }
    
    // Initialize on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFAQ);
    } else {
        initFAQ();
    }
})();
