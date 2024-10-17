<?php

namespace Drupal\openai_integration\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;

class OpenAIService {
    protected $apiClient;
    protected $session;
    protected $logger;
    protected $configFactory;
    protected $messenger;
    protected $model;
    protected $maxTokens;
    protected $temperature;
    protected $maxConversationLength;

    const MAX_PROMPT_LENGTH = 4096;

    public function __construct(
        OpenAIAPIClientInterface $apiClient,
        SessionInterface $session,
        LoggerChannelFactoryInterface $loggerFactory,
        ConfigFactoryInterface $configFactory,
        MessengerInterface $messenger
    ) {
        $this->apiClient = $apiClient;
        $this->session = $session;
        $this->logger = $loggerFactory->get('openai_integration');
        $this->configFactory = $configFactory;
        $this->messenger = $messenger;
        $this->loadConfig();
    }

    private function loadConfig() {
        $config = $this->configFactory->get('openai_integration.settings');
        $this->model = $config->get('model_name');
        $this->maxTokens = $config->get('max_tokens') ?? 150;
        $this->temperature = $config->get('temperature') ?? 0.7;
        $this->maxConversationLength = $config->get('max_conversation_length') ?? 10;
    }

    public function generateResponse($prompt) {
        if ($this->validatePrompt($prompt)) {
            $this->addToConversation('user', $prompt);
            $this->addSystemPrompt();
            return $this->fetchResponse();
        }
        return null;
    }

    private function validatePrompt(&$prompt) {
        $prompt = htmlspecialchars(strip_tags($prompt), ENT_QUOTES, 'UTF-8');
        if (strlen($prompt) > self::MAX_PROMPT_LENGTH) {
            $this->messenger->addError('The prompt is too long. Please reduce the length and try again.');
            return false;
        }
        return true;
    }

    private function addToConversation($role, $content) {
        $conversation = $this->getConversationHistory();
        $conversation[] = ['role' => $role, 'content' => $content];
        $conversation = array_slice($conversation, -$this->maxConversationLength);
        $this->saveConversationHistory($conversation);
    }

    private function fetchResponse() {
        try {
            $conversation = $this->getConversationHistory();
            $response = $this->apiClient->sendRequest('/chat/completions', [
                'model' => $this->model,
                'messages' => $conversation,
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
            ]);
            $responseContent = $response['choices'][0]['message']['content'] ?? 'No response content available.';
            $this->addToConversation('assistant', $responseContent);
            return $responseContent;
        } catch (\Exception $e) {
            $this->handleResponseError($e);
        }
        return null;
    }

    private function addSystemPrompt() {
        $systemPrompt = $this->configFactory->get('openai_integration.settings')->get('system_prompt');
        if (!empty($systemPrompt)) {
            $this->addToConversation('system', $systemPrompt);
        }
    }

    private function handleResponseError(\Exception $e) {
        $this->logger->error('Failed to generate response from model: @error', ['@error' => $e->getMessage()]);
        $this->messenger->addError('Sorry, an error occurred while processing your request. Please try again.');
    }

    public function getConversationHistory() {
        return $this->session->get('conversation_history', []);
    }

    public function saveConversationHistory(array $history) {
        $this->session->set('conversation_history', $history);
    }

    public function getAvailableModels() {
        return [
            'gpt-4o' => 'GPT-4o (Flagship)',
            'gpt-4o-mini' => 'GPT-4o mini (Affordable)',
            'o1-preview' => 'o1-preview (Reasoning)',
            'o1-mini' => 'o1-mini (Fast Reasoning)',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
        ];
    }

    public function checkApiKey($apiKey) {
        try {
            $this->apiClient->setApiKey($apiKey);
            $response = $this->apiClient->sendRequest('/models', [], 'GET');
            return isset($response['data']) && is_array($response['data']);
        } catch (\Exception $e) {
            $this->logger->error('API key validation failed: @error', ['@error' => $e->getMessage()]);
            return false;
        }
    }
}