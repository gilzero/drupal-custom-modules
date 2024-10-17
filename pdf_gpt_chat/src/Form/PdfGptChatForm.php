<?php

namespace Drupal\pdf_gpt_chat\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pdf_gpt_chat\Service\ChatProcessorService;
use Drupal\pdf_gpt_chat\Service\ErrorHandlerService;
use Drupal\pdf_gpt_chat\Service\FileHandlerService;
use Drupal\pdf_gpt_chat\Service\LoggingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PdfGptChatForm extends FormBase {

  protected $chatProcessor;
  protected $errorHandler;
  protected $fileHandler;
  protected $loggingService;

  public function __construct(
    ChatProcessorService $chat_processor,
    ErrorHandlerService $error_handler,
    FileHandlerService $file_handler,
    LoggingService $logging_service
  ) {
    $this->chatProcessor = $chat_processor;
    $this->errorHandler = $error_handler;
    $this->fileHandler = $file_handler;
    $this->loggingService = $logging_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('pdf_gpt_chat.chat_processor'),
      $container->get('pdf_gpt_chat.error_handler'),
      $container->get('pdf_gpt_chat.file_handler'),
      $container->get('pdf_gpt_chat.logging')
    );
  }

  public function getFormId() {
    return 'pdf_gpt_chat_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->loggingService->logSystemEvent('form_build', 'Building PDF GPT Chat form');
    
    $form['#attached']['library'][] = 'pdf_gpt_chat/pdf_gpt_chat';

    $validators = [
      'FileExtension' => [
        'extensions' => 'pdf',
      ],
    ];

    $form['pdf_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload PDF'),
      '#upload_location' => 'public://pdf_gpt_chat/',
      '#upload_validators' => $validators,
      '#required' => TRUE,
    ];

    $form['question'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Ask a question about the PDF'),
      '#required' => TRUE,
      '#attributes' => [
        'aria-label' => $this->t('Enter your question here'),
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Chat'),
      '#ajax' => [
        'callback' => '::ajaxSubmitCallback',
        'effect' => 'fade',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Processing...'),
        ],
      ],
    ];

    $form['chat_log'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'pdf-gpt-chat-log',
        'aria-live' => 'polite',
        'aria-relevant' => 'additions',
      ],
      '#weight' => 90,
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This method is intentionally left empty as we're using AJAX submission
  }

  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {
    $requestId = uniqid('chat_request_');
    
    try {
      $fid = $form_state->getValue('pdf_file')[0];
      $file = $this->fileHandler->validateAndLoadFile($fid);
      $question = $form_state->getValue('question');
  
      $this->loggingService->logSystemEvent('chat_process_start', 'Starting chat process', [
        'request_id' => $requestId,
        'file_id' => $fid,
        'user_id' => $this->currentUser()->id(),
      ]);
  
      $output = $this->chatProcessor->processChat($file, $question);
  
      $this->loggingService->logInteraction(
        $this->currentUser()->id(),
        $fid,
        $question,
        $output,
        $requestId
      );
  
      $ajax_response = new AjaxResponse();
      $ajax_response->addCommand(new AppendCommand('#pdf-gpt-chat-log', $output));
  
      $this->loggingService->logSystemEvent('chat_process_end', 'Chat process completed successfully', [
        'request_id' => $requestId,
        'file_id' => $fid,
        'user_id' => $this->currentUser()->id(),
      ]);
  
      return $ajax_response;
    }
    catch (\Exception $e) {
      $this->loggingService->logError('Error in chat process: ' . $e->getMessage(), [
        'request_id' => $requestId,
        'exception' => $e,
      ]);
      return $this->errorHandler->handleAjaxError($e);
    }
  }
}