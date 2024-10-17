<?php

namespace Drupal\pdf_gpt_chat\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Imagick;

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

  public function convertPdfToImages(File $file, $maxImages = 250) {
    $cid = 'pdf_gpt_chat:pdf_images:' . $file->id();
    
    $this->loggingService->logSystemEvent('pdf_conversion_start', 'Starting PDF to image conversion', [
      'file_id' => $file->id(),
      'file_name' => $file->getFilename(),
    ]);

    if ($cache = $this->cache->get($cid)) {
      $this->loggingService->logSystemEvent('pdf_conversion_cache_hit', 'PDF images retrieved from cache', [
        'file_id' => $file->id(),
      ]);
      return $cache->data;
    }

    try {
      $images = [];
      $imagick = new Imagick();
      $imagick->readImage($file->getFileUri());
      foreach ($imagick as $index => $page) {
        if ($index >= $maxImages) {
          break;
        }
        $page->setImageFormat('png');
        $images[] = base64_encode($page->getImageBlob());
      }

      $this->cache->set($cid, $images);

      $this->loggingService->logSystemEvent('pdf_conversion_success', 'PDF converted to images successfully', [
        'file_id' => $file->id(),
        'image_count' => count($images),
      ]);

      return $images;
    }
    catch (\Exception $e) {
      $this->loggingService->logError('PDF to image conversion failed: ' . $e->getMessage(), [
        'file_id' => $file->id(),
        'exception' => $e,
      ]);
      throw $e;
    }
  }
}