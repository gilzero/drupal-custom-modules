(function ($, Drupal) {
  Drupal.behaviors.pdfGptChat = {
    attach: function (context, settings) {
      once('pdfGptChat', '.pdf-gpt-chat-form', context).forEach(function (form) {
        const textarea = form.querySelector('textarea');
        const submitButton = form.querySelector('input[type="submit"]');
        const chatArea = document.getElementById('pdf-gpt-chat-log');

        // Auto-expanding textarea
        textarea.addEventListener('input', function () {
          this.style.height = 'auto';
          this.style.height = (this.scrollHeight) + 'px';
        });

        // Submit handling
        $(form).off('submit.pdfGptChat').on('submit.pdfGptChat', function (e) {
          e.preventDefault();
          submitButton.setAttribute('disabled', 'disabled');
          submitButton.value = Drupal.t('Processing...');
        });

        // Handle AJAX success
        $(document).ajaxComplete(function(event, xhr, settings) {
          if (settings.url === form.action) {
            textarea.value = '';
            textarea.style.height = 'auto';
            submitButton.removeAttribute('disabled');
            submitButton.value = Drupal.t('Chat');
            chatArea.scrollTop = chatArea.scrollHeight;
          }
        });
      });
    }
  };
})(jQuery, Drupal);