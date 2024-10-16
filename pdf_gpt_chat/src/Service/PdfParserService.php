<?php

/**
 * @file
 * Contains \Drupal\pdf_gpt_chat\Service\PdfParserService.php
 */

namespace Drupal\pdf_gpt_chat\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Smalot\PdfParser\Parser;

class PdfParserService {

  protected $fileSystem;
  protected $cache;

  public function __construct(FileSystemInterface $file_system, CacheBackendInterface $cache) {
    $this->fileSystem = $file_system;
    $this->cache = $cache;
  }

  public function extractText(File $file) {
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

}