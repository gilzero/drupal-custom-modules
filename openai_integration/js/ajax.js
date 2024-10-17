((Drupal, once) => {
    Drupal.behaviors.openaiIntegrationAjax = {
      attach(context, settings) {
        once('openaiIntegrationAjax', '#openai_integration_form', context).forEach((form) => {
          const promptInput = form.querySelector('#edit-prompt');
          const submitButton = form.querySelector('.form-submit');
  
          if (promptInput && submitButton) {
            this.initializeForm(promptInput, submitButton);
            this.setupEventListeners(form, promptInput, submitButton);
          }
        });
      },
  
      initializeForm(promptInput, submitButton) {
        this.toggleSubmitButtonState(promptInput.value.trim(), submitButton);
      },
  
      setupEventListeners(form, promptInput, submitButton) {
        const debounceToggleSubmit = Drupal.debounce(() => {
          this.toggleSubmitButtonState(promptInput.value.trim(), submitButton);
        }, 300);
  
        promptInput.addEventListener('input', debounceToggleSubmit);
        form.addEventListener('submit', (event) => this.handleFormSubmit(event, form, promptInput, submitButton));
      },
  
      toggleSubmitButtonState(promptValue, submitButton) {
        submitButton.disabled = promptValue === '';
      },
  
      handleFormSubmit(event, form, promptInput, submitButton) {
        event.preventDefault();
        if (promptInput.value.trim()) {
          this.submitForm(form, promptInput, submitButton);
        }
      },
  
      async submitForm(form, promptInput, submitButton) {
        if (!promptInput.value.trim()) return;
  
        this.showFeedback('Processing your request...');
  
        try {
          const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          });
  
          if (!response.ok) throw new Error('Network response was not ok');
  
          const data = await response.json();
          this.handleSuccess(data, promptInput, submitButton);
        } catch (error) {
          this.handleError(error, submitButton);
        }
      },
  
      handleSuccess(response, promptInput, submitButton) {
        this.clearFeedback();
        this.appendMessages(promptInput.value, response.data);
        promptInput.value = '';
        submitButton.disabled = false;
      },
  
      handleError(error, submitButton) {
        let errorMessage = 'Error processing request. Please try again.';
        errorMessage += ` Details: ${error.message}`;
        this.showFeedback(errorMessage, true);
        submitButton.disabled = false;
      },
  
      showFeedback(message, isError = false) {
        const feedbackField = document.getElementById('feedback-field');
        feedbackField.innerHTML = `<div class="alert ${isError ? 'alert-danger' : ''}">${message}</div>`;
      },
  
      clearFeedback() {
        document.getElementById('feedback-field').innerHTML = '';
      },
  
      appendMessages(userMessage, assistantMessage) {
        const messageBlock = `
          <div class="message user-message">${userMessage}</div>
          <div class="message assistant-message">${assistantMessage}</div>
        `;
        const conversationWrapper = document.getElementById('conversation-wrapper');
        conversationWrapper.insertAdjacentHTML('beforeend', messageBlock);
        conversationWrapper.scrollTop = conversationWrapper.scrollHeight;
      }
    };
  })(Drupal, once);