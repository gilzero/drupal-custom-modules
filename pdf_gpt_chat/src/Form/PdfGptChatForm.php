<?php

namespace Drupal\pdf_gpt_chat\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pdf_gpt_chat\Service\ChatProcessorService;
use Drupal\pdf_gpt_chat\Service\ErrorHandlerService;
use Drupal\pdf_gpt_chat\Service\FileHandlerService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PdfGptChatForm extends FormBase {

  protected $chatProcessor;
  protected $errorHandler;
  protected $fileHandler;

  public function __construct(
    ChatProcessorService $chat_processor,
    ErrorHandlerService $error_handler,
    FileHandlerService $file_handler
  ) {
    $this->chatProcessor = $chat_processor;
    $this->errorHandler = $error_handler;
    $this->fileHandler = $file_handler;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('pdf_gpt_chat.chat_processor'),
      $container->get('pdf_gpt_chat.error_handler'),
      $container->get('pdf_gpt_chat.file_handler')
    );
  }

  public function getFormId() {
    return 'pdf_gpt_chat_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
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
      '#attributes' => ['id' => 'pdf-gpt-chat-log'],
      '#weight' => 90,
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {}

  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {
    try {
      $fid = $form_state->getValue('pdf_file')[0];
      $file = $this->fileHandler->validateAndLoadFile($fid);
      $question = $form_state->getValue('question');

      $output = $this->chatProcessor->processChat($file, $question);

      $ajax_response = new AjaxResponse();
      $ajax_response->addCommand(new AppendCommand('#pdf-gpt-chat-log', $output));
      return $ajax_response;
    }
    catch (\Exception $e) {
      return $this->errorHandler->handleAjaxError($e);
    }
  }
}