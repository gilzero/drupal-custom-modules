(function ($, Drupal) {
    Drupal.behaviors.pdfGptChat = {
      attach: function (context, settings) {
        once('pdfGptChat', '.pdf-gpt-chat-form', context).forEach(function (form) {
          const textarea = form.querySelector('textarea');
          textarea.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
          });
  
          const submitButton = form.querySelector('input[type="submit"]');
          form.addEventListener('submit', function () {
            submitButton.setAttribute('disabled', 'disabled');
            submitButton.value = Drupal.t('Processing...');
          });
        });
      }
    };
  })(jQuery, Drupal);