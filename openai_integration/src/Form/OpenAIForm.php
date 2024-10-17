<?php

namespace Drupal\openai_integration\Form;

use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AppendCommand;
use Psr\Log\LoggerInterface;
use Drupal\openai_integration\Service\OpenAIService;
use Parsedown;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DependencyInjection\Attribute\Inject;

class OpenAIForm extends FormBase implements ContainerInjectionInterface {
    #[Inject]
    protected OpenAIService $openAIService;

    #[Inject]
    protected LoggerInterface $logger;

    #[Inject]
    protected AccountInterface $currentUser;

    const MAX_PROMPT_LENGTH = 4096;
    const FIELD_PROMPT = 'prompt';

    public function __construct(OpenAIService $openAIService, LoggerInterface $logger, AccountInterface $currentUser) {
        $this->openAIService = $openAIService;
        $this->logger = $logger;
        $this->currentUser = $currentUser;
    }

    public static function create(ContainerInterface $container): self {
        return new static(
            $container->get('openai_integration.openai_service'),
            $container->get('logger.factory')->get('openai_integration'),
            $container->get('current_user')
        );
    }

    public function getFormId() {
        return 'openai_integration_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['#attached']['library'][] = 'openai_integration/ajax';
        $form['#attached']['library'][] = 'openai_integration/styles';

        if (!$this->currentUser->hasPermission('submit openai form')) {
            $this->messenger()->addWarning($this->t('You do not have permission to access this form.'));
            return $form;
        }
        
        $form['conversation_wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
                'id' => 'conversation-wrapper',
                'aria-live' => 'polite',
                'aria-atomic' => 'true',
            ],
        ];

        $form[self::FIELD_PROMPT] = [
            '#type' => 'textarea',
            '#title' => $this->t('我是未名克隆体, 有什么可以帮您.😊'),
            '#description' => $this->t('Enter your message or question.'),
            '#required' => TRUE,
            '#attributes' => [
                'placeholder' => $this->t('Please type your command'),
                'required' => 'required',
                'aria-label' => $this->t('User prompt'),
            ],
        ];

        $form['actions'] = [
            '#type' => 'actions'
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Send'),
            '#ajax' => [
                'callback' => '::promptSubmitAjax',
                'wrapper' => 'conversation-wrapper',
            ],
        ];

        $form['actions']['clear'] = [
            '#type' => 'submit',
            '#value' => $this->t('Clear'),
            '#submit' => ['::clearConversation'],
            '#ajax' => [
                'callback' => '::clearConversationAjax',
                'wrapper' => 'conversation-wrapper',
            ],
        ];

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        $prompt = trim($form_state->getValue(self::FIELD_PROMPT));

        if (empty($prompt)) {
            $form_state->setErrorByName(self::FIELD_PROMPT, $this->t('The prompt cannot be empty.'));
        } elseif (strlen($prompt) > self::MAX_PROMPT_LENGTH) {
            $form_state->setErrorByName(self::FIELD_PROMPT, $this->t('The prompt is too long. Please limit to @max characters.', ['@max' => self::MAX_PROMPT_LENGTH]));
        }
    }

    public function promptSubmitAjax(array &$form, FormStateInterface $form_state) {
        $response = new AjaxResponse();
        if ($errors = $form_state->getErrors()) {
            return $this->handleAjaxErrors($form_state, $response);
        }
    
        $prompt = $form_state->getValue(self::FIELD_PROMPT);
        $responseText = $this->openAIService->generateResponse($prompt);
        
        if ($responseText === null) {
            $error_message = $this->t('Could not process your request. Please try again.');
            $response->addCommand(new HtmlCommand('.form-error-messages', $error_message));
            return $response;
        }
    
        return $this->appendResponseToConversation($response, $prompt, $responseText);
    }

    protected function handleAjaxErrors(FormStateInterface $form_state, AjaxResponse $response) {
        $renderer = \Drupal::service('renderer');
        $errorMessages = ['#type' => 'status_messages'];
        $renderedMessages = $renderer->renderRoot($errorMessages);
        $response->addCommand(new HtmlCommand('.form-error-messages', $renderedMessages));
        return $response;
    }

    protected function appendResponseToConversation(AjaxResponse $response, $prompt, $responseText) {
        $parser = new Parsedown();
        $htmlResponse = $parser->text($responseText);
        $safePrompt = Html::escape($prompt);
        $userMarkup = '<div class="message user-message">' . $safePrompt . '</div>';
        $assistantMarkup = '<div class="message assistant-message">' . $htmlResponse . '</div>';

        $response->addCommand(new InvokeCommand('#edit-prompt', 'val', ['']));
        $response->addCommand(new AppendCommand('#conversation-wrapper', $userMarkup));
        $response->addCommand(new AppendCommand('#conversation-wrapper', $assistantMarkup));

        return $response;
    }

    public function clearConversation(array &$form, FormStateInterface $form_state) {
        $this->openAIService->saveConversationHistory([]);
        $this->messenger()->addMessage($this->t('The conversation has been cleared.'));
    }

    public function clearConversationAjax(array &$form, FormStateInterface $form_state) {
        $response = new AjaxResponse();
        $response->addCommand(new HtmlCommand('#conversation-wrapper', ''));
        return $response;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
    }

    public function access(AccountInterface $account, $return_as_object = FALSE) {
        return $account->hasPermission('submit openai form');
    }
}