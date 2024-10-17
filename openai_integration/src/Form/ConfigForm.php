<?php

namespace Drupal\openai_integration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\openai_integration\Service\OpenAIService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigForm extends ConfigFormBase {
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService) {
        $this->openAIService = $openAIService;
    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('openai_integration.openai_service')
        );
    }

    protected function getEditableConfigNames() {
        return ['openai_integration.settings'];
    }

    public function getFormId() {
        return 'openai_integration_admin_settings';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('openai_integration.settings');

        $form['openai_api_key'] = [
            '#type' => 'textarea',
            '#title' => $this->t('OpenAI API Key'),
            '#default_value' => $config->get('openai_api_key'),
            '#required' => TRUE,
            '#description' => $this->t('Enter your OpenAI API key. This field supports long API keys.'),
            '#rows' => 2,
            '#ajax' => [
                'callback' => '::validateApiKeyAjax',
                'wrapper' => 'api-key-validation-message',
                'event' => 'change',
                'progress' => [
                    'type' => 'throbber',
                    'message' => $this->t('Validating...'),
                ],
            ],
            '#suffix' => '<div id="api-key-validation-message"></div>'
        ];

        $form['model_name'] = [
            '#type' => 'select',
            '#title' => $this->t('Model Name'),
            '#default_value' => $config->get('model_name'),
            '#options' => $this->openAIService->getAvailableModels(),
            '#required' => TRUE,
            '#description' => $this->t('Select the AI model for your integration. GPT-4o is the flagship model for complex tasks, while GPT-4o mini is more affordable for simpler tasks.'),
        ];

        $form['system_prompt'] = [
            '#type' => 'textarea',
            '#title' => $this->t('System Prompt'),
            '#default_value' => $config->get('system_prompt'),
            '#description' => $this->t('Enter a system prompt or default question for the AI.'),
            '#rows' => 5,
            '#required' => FALSE,
        ];

        return parent::buildForm($form, $form_state);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        $apiKey = trim($form_state->getValue('openai_api_key'));
        if (empty($apiKey)) {
            $form_state->setErrorByName('openai_api_key', $this->t('API key is required.'));
        }
        
        $modelName = $form_state->getValue('model_name');
        if (empty($modelName)) {
            $form_state->setErrorByName('model_name', $this->t('Model name is required.'));
        }
        
        $systemPrompt = $form_state->getValue('system_prompt');
        if (strlen($systemPrompt) > 10000) {
            $form_state->setErrorByName('system_prompt', $this->t('System prompt should not exceed 10000 characters.'));
        }
    }

    public function validateApiKeyAjax(array &$form, FormStateInterface $form_state) {
        $response = new AjaxResponse();
        $apiKey = trim($form_state->getValue('openai_api_key'));
    
        try {
            $isValid = $this->openAIService->checkApiKey($apiKey);
            $cssClass = $isValid ? 'response-valid' : 'response-invalid';
            $message = $isValid ? $this->t('API Key is valid.') : $this->t('API Key is invalid.');
        } catch (\Exception $e) {
            $message = $this->t('Failed to verify API Key: @error', ['@error' => $e->getMessage()]);
            $cssClass = 'response-error';
        }

        $response->addCommand(new HtmlCommand('#api-key-validation-message', "<div class=\"{$cssClass}\">{$message}</div>"));
        return $response;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        parent::submitForm($form, $form_state);
        $this->config('openai_integration.settings')
            ->set('openai_api_key', trim($form_state->getValue('openai_api_key')))
            ->set('model_name', $form_state->getValue('model_name'))
            ->set('system_prompt', $form_state->getValue('system_prompt'))
            ->save();
    }
}