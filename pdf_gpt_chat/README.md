# PDF GPT Chat Module for Drupal 11

## dev note
- v7. working state
- v8  working state
- v9 working state
- v10 working state

## Overview

The PDF GPT Chat module for Drupal 11 allows users to upload PDF documents and ask questions about their content using OpenAI's GPT models. This module provides an interactive chat interface where users can engage with the AI to get information and insights from their PDF documents.

## Features

- PDF upload and parsing
- Integration with OpenAI's GPT models
- Interactive chat interface
- Configurable OpenAI settings (API key, model, max tokens, temperature)
- Chat history storage
- Markdown rendering for AI responses
- AJAX-based chat for a smooth user experience
- Error handling and user feedback
- Responsive design for mobile compatibility

## Requirements

- Drupal 11
- PHP 8.3 or higher
- OpenAI API key

## Installation

1. Download and place the `pdf_gpt_chat` folder in your Drupal installation's `modules/custom` directory.
2. Enable the module through the Drupal admin interface or use Drush:
`drush en pdf_gpt_chat`


## Configuration

1. Navigate to `Admin > Configuration > System > PDF GPT Chat Settings` (`/admin/config/system/pdf-gpt-chat`).
2. Enter your OpenAI API key.
3. Select the desired OpenAI model (default: gpt-3.5-turbo).
4. Set the maximum number of tokens for responses (default: 4096).
5. Adjust the temperature setting for response generation (default: 0.7).
6. Save the configuration.

## Usage

1. Navigate to the PDF GPT Chat page (`/pdf-gpt-chat`).
2. Upload a PDF document using the provided form.
3. Enter a question about the PDF content in the text area.
4. Click the "Chat" button to submit your question.
5. The AI will process your question and provide an answer based on the PDF content.
6. Continue the conversation by asking more questions as needed.

## Customization

- Modify the `pdf_gpt_chat.css` file to adjust the chat interface styling.
- Edit the `pdf-gpt-chat-result.html.twig` template to change the output format of chat messages.
- Extend the `ChatProcessorService` to add additional processing steps or integrate with other services.

## Troubleshooting

- If you encounter issues with PDF parsing, ensure that the PDF file is not corrupted and is readable.
- Check the Drupal logs for any error messages related to the OpenAI API communication.
- Verify that your OpenAI API key is valid and has sufficient credits.

## Contributing

Contributions to the PDF GPT Chat module are welcome! Please submit issues and pull requests on the project's GitHub repository.

## License

This module is open-source software licensed under the GNU General Public License version 2 or later.

## Acknowledgements

This module uses the following libraries:
- [Smalot/PdfParser](https://github.com/smalot/pdfparser) for PDF text extraction
- [Parsedown](https://github.com/erusev/parsedown) for Markdown rendering

## Version History

For the latest updates and more information, please visit the module's GitHub repository.

## Author

Weiming Chen @gilzero