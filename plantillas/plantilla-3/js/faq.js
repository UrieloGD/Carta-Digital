function toggleFAQ(index) {
    const answer = document.getElementById(`faq-${index}`);
    const arrow = answer.previousElementSibling.querySelector('.faq-arrow');
    
    if (answer.style.display === 'block') {
        answer.style.display = 'none';
        arrow.textContent = '▼';
    } else {
        answer.style.display = 'block';
        arrow.textContent = '▲';
    }
}