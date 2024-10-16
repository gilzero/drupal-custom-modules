<?php

/**
 * @file
 * Contains \Drupal\pdf_gpt_chat\Form\PdfGptChatForm.php
 */

namespace Drupal\pdf_gpt_chat\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\file\Entity\File;
use Drupal\file\Validation\FileValidatorInterface;
use Drupal\pdf_gpt_chat\Service\ChatHistoryService;
use Drupal\pdf_gpt_chat\Service\MessageFormatterService;
use Drupal\pdf_gpt_chat\Service\OpenAIService;
use Drupal\pdf_gpt_chat\Service\PdfParserService;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PdfGptChatForm extends FormBase {

  protected $httpClient;
  protected $configFactory;
  protected $fileSystem;
  protected $messenger;
  protected $fileValidator;
  protected $pdfParser;
  protected $openAI;
  protected $chatHistory;
  protected $messageFormatter;
  protected $cache;

  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    FileSystemInterface $file_system,
    MessengerInterface $messenger,
    FileValidatorInterface $file_validator,
    PdfParserService $pdf_parser,
    OpenAIService $openai,
    ChatHistoryService $chat_history,
    MessageFormatterService $message_formatter,
    CacheBackendInterface $cache
  ) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
    $this->messenger = $messenger;
    $this->fileValidator = $file_validator;
    $this->pdfParser = $pdf_parser;
    $this->openAI = $openai;
    $this->chatHistory = $chat_history;
    $this->messageFormatter = $message_formatter;
    $this->cache = $cache;
    $this->ensureUploadDirectory();
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('file_system'),
      $container->get('messenger'),
      $container->get('file.validator'),
      $container->get('pdf_gpt_chat.pdf_parser'),
      $container->get('pdf_gpt_chat.openai'),
      $container->get('pdf_gpt_chat.chat_history'),
      $container->get('pdf_gpt_chat.message_formatter'),
      $container->get('cache.default')
    );
  }


  protected function ensureUploadDirectory() {
    $directory = 'public://pdf_gpt_chat';
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
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
    $fid = $form_state->getValue('pdf_file')[0];
    $file = File::load($fid);

    if (!$file) {
      return $this->ajaxErrorResponse($this->t('Failed to load the uploaded file.'));
    }

    $validators = [
      'FileExtension' => [
        'extensions' => 'pdf',
      ],
    ];

    $violations = $this->fileValidator->validate($file, $validators);
    if (count($violations) > 0) {
      $errors = [];
      foreach ($violations as $violation) {
        $errors[] = $violation->getMessage();
      }
      return $this->ajaxErrorResponse(implode('<br>', $errors));
    }


    $question = $form_state->getValue('question');

    try {
      $pdf_text = $this->pdfParser->extractText($file);
      $prompt = $this->preparePrompt($pdf_text, $question);
      $response = $this->openAI->query($prompt);

      $output = $this->messageFormatter->formatMessage($question, $response);

      $this->chatHistory->saveMessage($this->currentUser()->id(), $file->id(), $question, $response);


      $ajax_response = new AjaxResponse();
      $ajax_response->addCommand(new AppendCommand('#pdf-gpt-chat-log', $output));
      return $ajax_response;
    }
    catch (\Exception $e) {
      return $this->ajaxErrorResponse($this->t('An error occurred: @error', ['@error' => $e->getMessage()]));
    }
  }


  protected function ajaxErrorResponse($message) {
    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new AppendCommand('#pdf-gpt-chat-log', '<div class="error-message">' . $message . '</div>'));
    return $ajax_response;
  }

  protected function preparePrompt($pdf_text, $question) {
    return "PDF Content: $pdf_text\n\nQuestion: $question";
  }


}