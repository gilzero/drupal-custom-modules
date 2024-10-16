<?php

namespace Drupal\pdf_gpt_chat\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Smalot\PdfParser\Parser;

class PdfParserService {

  protected $fileSystem;
  protected $cache;
  protected $loggingService;

  public function __construct(
    FileSystemInterface $file_system,
    CacheBackendInterface $cache,
    LoggingService $logging_service
  ) {
    $this->fileSystem = $file_system;
    $this->cache = $cache;
    $this->loggingService = $logging_service;
  }

  public function extractText(File $file) {
    $cid = 'pdf_gpt_chat:pdf_content:' . $file->id();
    
    $this->loggingService->logSystemEvent('pdf_parse_start', 'Starting PDF parsing', [
      'file_id' => $file->id(),
      'file_name' => $file->getFilename(),
    ]);

    if ($cache = $this->cache->get($cid)) {
      $this->loggingService->logSystemEvent('pdf_parse_cache_hit', 'PDF content retrieved from cache', [
        'file_id' => $file->id(),
      ]);
      return $cache->data;
    }

    try {
      $parser = new Parser();
      $pdf = $parser->parseFile($file->getFileUri());
      $text = $pdf->getText();

      $this->cache->set($cid, $text);

      $this->loggingService->logSystemEvent('pdf_parse_success', 'PDF parsed successfully', [
        'file_id' => $file->id(),
        'text_length' => strlen($text),
      ]);

      return $text;
    }
    catch (\Exception $e) {
      $this->loggingService->logError('PDF parsing failed: ' . $e->getMessage(), [
        'file_id' => $file->id(),
        'exception' => $e,
      ]);
      throw $e;
    }
  }
}