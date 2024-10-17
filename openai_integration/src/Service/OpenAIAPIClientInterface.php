<?php

namespace Drupal\openai_integration\Service;

interface OpenAIAPIClientInterface {
    public function sendRequest($methodName, array $payload, $method = 'POST');
}