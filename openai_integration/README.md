# OpenAI Integration Module for Drupal

The OpenAI Integration module provides seamless integration between Drupal and the OpenAI API, allowing you to incorporate AI-powered chatbot functionality into your Drupal website. With this module, you can engage users in natural language conversations and provide automated responses using OpenAI's advanced language models.

## Features

- Integrated chat interface for users to interact with the AI assistant
- Support for various OpenAI language models, including GPT-3.5, GPT-4, and GPT-4 Turbo
- Customizable system prompts and default responses
- Conversation history management and storage
- AJAX-based chat interactions for seamless user experience
- Configuration options for API key, model selection, and system prompts
- Built-in error handling and logging for easy debugging
- Accessibility-friendly chat interface with ARIA attributes
- Input validation and sanitization for enhanced security

## Requirements

- Drupal 9.x, 10.x, or 11.x
- PHP 8.1 or later
- OpenAI API key

## Installation

1. Download the OpenAI Integration module from the Drupal.org project page or via Composer:
`composer require drupal/openai_integration`


2. Enable the module using Drush or through the Drupal admin interface:

`drush en openai_integration`


3. Configure the module settings at `/admin/config/services/openai`:
- Enter your OpenAI API key
- Select the desired language model
- Customize the system prompt (optional)

4. Grant the necessary permissions to users who should have access to the chat interface.

## Usage

1. Navigate to the chat interface at `/hi`.

2. Type your message or question into the input field and click "Send" or press Enter.

3. The AI assistant will process your input and provide a response based on the selected language model and system prompt.

4. Continue the conversation by entering more messages or questions.

5. To clear the conversation history, click the "Clear" button.

## Configuration

The module provides a configuration form at `/admin/config/services/openai` where you can customize the following settings:

- OpenAI API Key: Enter your OpenAI API key to authenticate with the OpenAI API.
- Model Name: Select the desired language model for generating responses (e.g., GPT-3.5, GPT-4, GPT-4 Turbo).
- System Prompt: Customize the initial prompt or instructions for the AI assistant (optional).

Make sure to save the configuration after making any changes.

## Troubleshooting

If you encounter any issues while using the OpenAI Integration module, please check the following:

- Ensure that your OpenAI API key is valid and has the necessary permissions.
- Verify that the selected language model is available and supported by your OpenAI account.
- Check the Drupal logs for any error messages or warnings related to the module.
- If the chat interface is not responding or displaying errors, clear the Drupal cache and try again.

If the problem persists, please file an issue in the module's issue queue on Drupal.org or contact the module maintainer for further assistance.

## Contributing

Contributions to the OpenAI Integration module are welcome! If you encounter any bugs, have feature requests, or would like to contribute improvements, please follow the contribution guidelines outlined in the CONTRIBUTING.md file.

## License

This module is licensed under the GNU General Public License v2.0 or later.

## Credits

The OpenAI Integration module is maintained by Weiming and is not affiliated with or endorsed by OpenAI.

## Contact

For questions, suggestions, or support requests, please contact the module maintainer at [Your Email Address].