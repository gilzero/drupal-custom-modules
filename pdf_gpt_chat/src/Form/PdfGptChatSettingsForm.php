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

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('pdf_gpt_chat.settings')
      ->set('openai_api_key', $form_state->getValue('openai_api_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}