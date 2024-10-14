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

class PdfGptChatForm extends FormBase {

  protected $httpClient;
  protected $configFactory;
  protected $fileSystem;
  protected $messenger;

  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    FileSystemInterface $file_system,
    MessengerInterface $messenger
  ) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->fileSystem = $file_system;
    $this->messenger = $messenger;
    $this->ensureUploadDirectory();
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('config.factory'),
      $container->get('file_system'),
      $container->get('messenger')
    );
  }

  protected function ensureUploadDirectory() {
    $directory = 'public://pdf_gpt_chat';
    if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      $this->messenger->addError($this->t('The upload directory %directory could not be created or is not writable. Please contact the site administrator.', ['%directory' => $directory]));
    }
  }

  public function getFormId() {
    return 'pdf_gpt_chat_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['pdf_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload PDF'),
      '#upload_location' => 'public://pdf_gpt_chat/',
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
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fid = $form_state->getValue('pdf_file')[0];
    $file = File::load($fid);

    if (!$file) {
      $this->messenger->addError($this->t('Failed to load the uploaded file.'));
      return;
    }

    $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
    if (strtolower($extension) !== 'pdf') {
      $this->messenger->addError($this->t('Only PDF files are allowed.'));
      return;
    }

    $question = $form_state->getValue('question');

    try {
      $pdf_text = $this->extractTextFromPdf($file);
      $prompt = $this->preparePrompt($pdf_text, $question);
      $response = $this->sendToOpenAI($prompt);

      $this->messenger->addMessage($this->t('Response: @response', ['@response' => $response]));
    }
    catch (\Exception $e) {
      $this->messenger->addError($this->t('An error occurred while processing your request: @error', ['@error' => $e->getMessage()]));
    }
  }

  protected function extractTextFromPdf($file) {
    $parser = new Parser();
    $pdf = $parser->parseFile($this->fileSystem->realpath($file->getFileUri()));
    return $pdf->getText();
  }

  protected function preparePrompt($pdf_text, $question) {
    return "PDF Content: $pdf_text\n\nQuestion: $question";
  }

  protected function sendToOpenAI($prompt) {
    $api_key = $this->configFactory->get('pdf_gpt_chat.settings')->get('openai_api_key');
    
    if (!$api_key) {
      throw new \Exception($this->t('OpenAI API key is not configured.'));
    }

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
  }

}
