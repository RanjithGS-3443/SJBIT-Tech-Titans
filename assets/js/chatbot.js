/**
 * Career Roadmap Generator - Chatbot JavaScript
 * Handles AJAX requests and UI interactions for the chatbot
 */

document.addEventListener('DOMContentLoaded', function() {
    const chatContainer = document.getElementById('chatContainer');
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const typingIndicator = document.getElementById('typingIndicator');
    
    // Scroll to bottom of chat
    function scrollToBottom() {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
    // Handle form submission with AJAX
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const message = messageInput.value.trim();
        
        if (message) {
            // Add user message to chat
            const userMessageDiv = document.createElement('div');
            userMessageDiv.className = 'user-message';
            userMessageDiv.innerHTML = `<p class="mb-0">${escapeHtml(message)}</p>`;
            chatContainer.appendChild(userMessageDiv);
            
            // Show typing indicator
            typingIndicator.style.display = 'block';
            chatContainer.appendChild(typingIndicator);
            
            // Scroll to bottom
            scrollToBottom();
            
            // Clear input
            messageInput.value = '';
            
            // Send AJAX request
            const formData = new FormData();
            formData.append('message', message);
            
            fetch('chatbot.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Hide typing indicator
                typingIndicator.style.display = 'none';
                
                // Create a temporary container to parse the HTML
                const tempContainer = document.createElement('div');
                tempContainer.innerHTML = html;
                
                // Extract the bot response
                const botResponse = tempContainer.querySelector('.bot-message:last-child');
                
                if (botResponse) {
                    // Add bot response to chat
                    chatContainer.appendChild(botResponse.cloneNode(true));
                    
                    // Scroll to bottom
                    scrollToBottom();
                } else {
                    // Handle error
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'alert alert-danger mt-3';
                    errorDiv.textContent = 'Sorry, there was an error processing your request. Please try again.';
                    chatContainer.appendChild(errorDiv);
                    scrollToBottom();
                }
            })
            .catch(error => {
                // Hide typing indicator
                typingIndicator.style.display = 'none';
                
                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger mt-3';
                errorDiv.textContent = 'Sorry, there was an error connecting to the server. Please try again.';
                chatContainer.appendChild(errorDiv);
                scrollToBottom();
                
                console.error('Error:', error);
            });
        }
    });
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Add keyboard shortcut (Ctrl+Enter) to submit the form
    messageInput.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter') {
            chatForm.dispatchEvent(new Event('submit'));
        }
    });
    
    // Add placeholder suggestions
    const suggestions = [
        "What skills should I focus on for my career goals?",
        "How can I improve my current skill level?",
        "What resources do you recommend for learning?",
        "How can I track my progress effectively?",
        "What career paths match my current skills?"
    ];
    
    // Create suggestions container
    const suggestionsContainer = document.createElement('div');
    suggestionsContainer.className = 'suggestions-container mt-2';
    suggestionsContainer.innerHTML = '<small class="text-muted">Try asking:</small>';
    
    // Add suggestion buttons
    suggestions.forEach(suggestion => {
        const button = document.createElement('button');
        button.className = 'btn btn-sm btn-outline-secondary me-2 mb-2';
        button.textContent = suggestion;
        button.addEventListener('click', function() {
            messageInput.value = suggestion;
            messageInput.focus();
        });
        suggestionsContainer.appendChild(button);
    });
    
    // Add suggestions after the form
    chatForm.parentNode.insertBefore(suggestionsContainer, chatForm.nextSibling);
}); 