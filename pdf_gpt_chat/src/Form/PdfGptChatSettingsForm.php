<?php

namespace Drupal\pdf_gpt_chat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pdf_gpt_chat\Service\LoggingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PdfGptChatSettingsForm extends ConfigFormBase {

  protected $loggingService;

  public function __construct(LoggingService $logging_service) {
    $this->loggingService = $logging_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('pdf_gpt_chat.logging')
    );
  }

  public function getFormId() {
    return 'pdf_gpt_chat_settings_form';
  }

  protected function getEditableConfigNames() {
    return ['pdf_gpt_chat.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pdf_gpt_chat.settings');
    
    $this->loggingService->logSystemEvent('settings_form_build', 'Building PDF GPT Chat settings form');

    $form['openai_api_key'] = [
      '#type' => 'textarea',
      '#title' => $this->t('OpenAI API Key'),
      '#description' => $this->t('Enter your OpenAI API key. Use a textarea to accommodate longer keys.'),
      '#default_value' => $config->get('openai_api_key'),
      '#required' => TRUE,
      '#rows' => 2,
      '#resizable' => 'vertical',
    ];
  
    $form['system_prompt'] = [
      '#type' => 'textarea',
      '#title' => $this->t('System Prompt'),
      '#description' => $this->t('Enter the system prompt to be used for the AI. This sets the context for the AI\'s responses.'),
      '#default_value' => $config->get('system_prompt') ?: 'You are a helpful assistant that answers questions about PDF documents.',
      '#required' => TRUE,
      '#rows' => 4,
      '#resizable' => 'vertical',
    ];
  
    $form['openai_model'] = [
      '#type' => 'select',
      '#title' => $this->t('OpenAI Model'),
      '#description' => $this->t('Select the OpenAI model to use.'),
      '#options' => [
        'gpt-4o' => $this->t('GPT-4o'),
        'gpt-4o-mini' => $this->t('GPT-4o mini'),
        'gpt-4-turbo' => $this->t('GPT-4 Turbo'),
        'gpt-3.5-turbo' => $this->t('GPT-3.5 Turbo'),
      ],
      '#default_value' => $config->get('openai_model') ?: 'gpt-4o',
    ];
  
    $form['max_tokens'] = [
      '#type' => 'number',
      '#title' => $this->t('Max Tokens'),
      '#description' => $this->t('Set the maximum number of tokens for the response.'),
      '#default_value' => $config->get('max_tokens') ?: 4096,
      '#min' => 1,
      '#max' => 16384,
    ];
  
    $form['temperature'] = [
      '#type' => 'number',
      '#title' => $this->t('Temperature'),
      '#description' => $this->t('Set the temperature for response generation (0-2).'),
      '#default_value' => $config->get('temperature') ?: 0.7,
      '#min' => 0,
      '#max' => 2,
      '#step' => 0.1,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('pdf_gpt_chat.settings')
      ->set('openai_api_key', trim($form_state->getValue('openai_api_key')))
      ->set('system_prompt', $form_state->getValue('system_prompt'))
      ->set('openai_model', $form_state->getValue('openai_model'))
      ->set('max_tokens', $form_state->getValue('max_tokens'))
      ->set('temperature', $form_state->getValue('temperature'))
      ->save();

    $this->loggingService->logSystemEvent('settings_form_submit', 'PDF GPT Chat settings updated', [
      'openai_model' => $form_state->getValue('openai_model'),
      'max_tokens' => $form_state->getValue('max_tokens'),
      'temperature' => $form_state->getValue('temperature'),
    ]);

    parent::submitForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $api_key = $form_state->getValue('openai_api_key');
    if (preg_match('/\s/', $api_key)) {
      $form_state->setErrorByName('openai_api_key', $this->t('The API key should not contain any whitespace characters.'));
      $this->loggingService->logError('Invalid API key format in settings form');
    }
  }
}