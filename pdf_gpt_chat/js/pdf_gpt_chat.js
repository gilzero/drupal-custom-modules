(function ($, Drupal) {
  Drupal.behaviors.pdfGptChat = {
    attach: function (context, settings) {
      once('pdfGptChat', '.pdf-gpt-chat-form', context).forEach(function (form) {
        const textarea = form.querySelector('textarea');
        const submitButton = form.querySelector('input[type="submit"]');
        const chatArea = document.getElementById('pdf-gpt-chat-log');

        textarea.addEventListener('input', function () {
          this.style.height = 'auto';
          this.style.height = (this.scrollHeight) + 'px';
        });

        $(form).off('submit.pdfGptChat').on('submit.pdfGptChat', function (e) {
          e.preventDefault();
          submitButton.setAttribute('disabled', 'disabled');
          submitButton.value = Drupal.t('Processing...');
          showTypingIndicator();
        });

        $(document).ajaxComplete(function(event, xhr, settings) {
          if (settings.url === form.action) {
            textarea.value = '';
            textarea.style.height = 'auto';
            submitButton.removeAttribute('disabled');
            submitButton.value = Drupal.t('Chat');
            chatArea.scrollTop = chatArea.scrollHeight;
            hideTypingIndicator();
          }
        });

        function showTypingIndicator() {
          const indicator = document.createElement('div');
          indicator.className = 'typing-indicator';
          indicator.innerHTML = '<span></span><span></span><span></span>';
          chatArea.appendChild(indicator);
        }

        function hideTypingIndicator() {
          const indicator = chatArea.querySelector('.typing-indicator');
          if (indicator) {
            indicator.remove();
          }
        }

        new MutationObserver(function(mutations) {
          mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
              const addedNodes = mutation.addedNodes;
              for (let i = 0; i < addedNodes.length; i++) {
                if (addedNodes[i].nodeType === 1 && addedNodes[i].classList.contains('chat-message')) {
                  addedNodes[i].setAttribute('tabindex', '0');
                }
              }
            }
          });
        }).observe(chatArea, { childList: true });

        document.addEventListener('keydown', function(e) {
          if (e.key === 'Tab') {
            const focusableElements = chatArea.querySelectorAll('.chat-message');
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (e.shiftKey) {
              if (document.activeElement === firstElement) {
                lastElement.focus();
                e.preventDefault();
              }
            } else {
              if (document.activeElement === lastElement) {
                firstElement.focus();
                e.preventDefault();
              }
            }
          }
        });
      });
    }
  };
})(jQuery, Drupal);