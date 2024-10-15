<?php

namespace Drupal\pdf_gpt_chat\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Smalot\PdfParser\Parser;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\file\Validation\FileValidatorInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Cache\CacheBackendInterface;
use GuzzleHttp\Exception\RequestException;
use Parsedown;

class PdfGptChatForm extends FormBase {

  protected $httpClient;
  protected $configFactory;
  protected $fileSystem;
  protected $messenger;
  protected $fileValidator;
  protected $cache;

  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    FileSystemInterface $file_system,
    MessengerInterface $messenger,
    FileValidatorInterface $file_validator,
    CacheBackendInterface $cache
  ) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
    $this->messenger = $messenger;
    $this->fileValidator = $file_validator;
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
      $pdf_text = $this->extractTextFromPdf($file);
      $prompt = $this->preparePrompt($pdf_text, $question);
      $response = $this->sendToOpenAI($prompt);

      $output = $this->formatResponse($question, $response);

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

  protected function extractTextFromPdf($file) {
    $cid = 'pdf_gpt_chat:pdf_content:' . $file->id();
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $parser = new Parser();
    $pdf = $parser->parseFile($file->getFileUri());
    $text = $pdf->getText();

    $this->cache->set($cid, $text);

    return $text;
  }

  protected function preparePrompt($pdf_text, $question) {
    return "PDF Content: $pdf_text\n\nQuestion: $question";
  }

  protected function sendToOpenAI($prompt) {
    $api_key = $this->configFactory->get('pdf_gpt_chat.settings')->get('openai_api_key');
    
    if (!$api_key) {
      throw new \Exception($this->t('OpenAI API key is not configured.'));
    }

    try {
      $response = $this->httpClient->post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
          'Authorization' => 'Bearer ' . $api_key,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => 'gpt-4o-mini',
          'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful assistant that answers questions about PDF documents.'],
            ['role' => 'user', 'content' => $prompt],
          ],
          'max_tokens' => 150,
        ],
      ]);

      $result = json_decode($response->getBody(), TRUE);
      return $result['choices'][0]['message']['content'] ?? 'No response generated.';
    } catch (RequestException $e) {
      $this->logger('pdf_gpt_chat')->error('OpenAI API request failed: @error', ['@error' => $e->getMessage()]);
      throw new \Exception($this->t('Failed to get a response from OpenAI. Please try again later.'));
    }
  }

  protected function formatResponse($question, $answer) {
    $parsedown = new Parsedown();
    $formatted = "<div class='chat-message user-message'><strong>You:</strong> " . nl2br(htmlspecialchars($question)) . "</div>";
    $formatted .= "<div class='chat-message ai-message'><strong>AI:</strong> <div class='markdown-content'>" . $parsedown->text($answer) . "</div></div>";
    return $formatted;
  }
}