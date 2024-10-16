<?php

namespace Drupal\pdf_gpt_chat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class PdfGptChatSettingsForm extends ConfigFormBase {

  public function getFormId() {
    return 'pdf_gpt_chat_settings_form';
  }

  protected function getEditableConfigNames() {
    return ['pdf_gpt_chat.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pdf_gpt_chat.settings');
  
    $form['openai_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OpenAI API Key'),
      '#description' => $this->t('Enter your OpenAI API key.'),
      '#default_value' => $config->get('openai_api_key'),
      '#required' => TRUE,
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
      '#default_value' => $config->get('openai_model') ?: 'gpt-3.5-turbo',
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
      ->set('openai_api_key', $form_state->getValue('openai_api_key'))
      ->set('openai_model', $form_state->getValue('openai_model'))
      ->set('max_tokens', $form_state->getValue('max_tokens'))
      ->set('temperature', $form_state->getValue('temperature'))
      ->save();
  
    parent::submitForm($form, $form_state);
  }
}