# PDF GPT Chat Module for Drupal 11

This module enhances user interaction with PDF documents by leveraging the power of OpenAI's GPT models. Upload a PDF, ask questions about its content, and receive intelligent, AI-generated answers.

## Features

* **PDF Text Extraction:** Extracts text content from uploaded PDF files using the smalot/pdfparser library.
* **OpenAI GPT Integration:** Seamlessly integrates with the OpenAI GPT API to generate responses to user queries. Uses the gpt-3.5-turbo model by default (configurable).
* **Interactive Chat Interface:** Provides a user-friendly chat interface for asking questions and viewing responses.
* **Chat History:** Persistently stores the conversation history for each PDF, allowing users to review previous interactions.
* **Markdown Rendering:** Renders AI responses using Markdown for improved readability and formatting (via the erusev/parsedown library).
* **Caching:** Caches both extracted PDF text and OpenAI API responses to optimize performance and reduce API usage costs.
* **Error Handling and Logging:** Provides robust error handling and logging to facilitate debugging and troubleshooting.

## Installation and Configuration

1. **Install the module:** Install the PDF GPT Chat module as you would any other Drupal module.

2. **Install required libraries:** This module depends on the following Composer libraries:

   ```
   composer require smalot/pdfparser erusev/parsedown
   ```

3. **Configure the module:**
   - Navigate to `/admin/config/system/pdf-gpt-chat`.
   - Securely store your OpenAI API key:
     - Do not enter your API key directly into the settings form.
     - Instead, use the Drupal Key module for secure key management:
       a. Install the Key module.
       b. Create a new key and paste your OpenAI API key as the key value.
       c. Select an appropriate key provider.
   - In your module's services configuration (pdf_gpt_chat.services.yml), configure the pdf_gpt_chat.openai service to use the key from the Key module. See the Key module's documentation for details on how to do this.

4. **Database setup:**
   - The module requires a database table to store the chat history. After installing the module, run database updates:
     - UI: Visit `/update.php`
     - Drush: `drush updatedb`

## Usage

1. Go to `/pdf-gpt-chat` to access the PDF GPT Chat interface.
2. Upload a PDF using the file upload field.
3. Type your question about the PDF content in the text area.
4. Click "Chat" to submit your question.
5. The AI's response will appear in the chat log below. Previous questions and answers are preserved in the history.

## Security Considerations

- **API Key Protection:** Using the Key module is crucial for protecting your OpenAI API key. Never expose your API key directly in configuration files or code.
- **File Upload Validation:** The module validates uploaded files to ensure they are PDFs. However, always follow best practices for secure file uploads, including limiting file size and validating MIME types.
- **Input Sanitization:** User inputs are sanitized to prevent cross-site scripting (XSS) vulnerabilities. However, regularly review and update sanitization methods as needed.

## Extending the Module

- **Customizations:** The module's services are designed to be extensible. You can create custom services to modify the behavior of PDF parsing, API interaction, or message formatting.
- **Event Subscribers:** Use Drupal's event system to react to module events and implement custom logic.

## Troubleshooting

- **Error Messages:** Pay attention to error messages displayed in the UI or logged to the database logs.
- **Debugging:** Use Drupal's debugging tools and the `@logger.factory` service to debug issues.

## Contributing

Contributions, bug reports, and feature requests are welcome! Please use the issue queue on the project's repository.

## License

This module is licensed under GPL-2.0-or-later.