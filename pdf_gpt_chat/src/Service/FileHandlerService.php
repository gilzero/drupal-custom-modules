<?php

namespace Drupal\pdf_gpt_chat\Service;

use Drupal\file\Entity\File;
use Drupal\file\Validation\FileValidatorInterface;
use Drupal\pdf_gpt_chat\Service\LoggingService;

class FileHandlerService {
  protected $fileValidator;
  protected $loggingService;

  public function __construct(FileValidatorInterface $file_validator, LoggingService $logging_service) {
    $this->fileValidator = $file_validator;
    $this->loggingService = $logging_service;
  }

  public function validateAndLoadFile($fid) {
    $file = File::load($fid);
    if (!$file) {
      $this->loggingService->logError('Failed to load the uploaded file.', ['file_id' => $fid]);
      throw new \Exception('Failed to load the uploaded file.');
    }
    $this->validateFileType($file);
    $this->loggingService->logSystemEvent('file_validated', 'File validated successfully', ['file_id' => $fid]);
    return $file;
  }

  protected function validateFileType(File $file) {
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
      $this->loggingService->logError('File validation failed', [
        'file_id' => $file->id(),
        'errors' => $errors,
      ]);
      throw new \Exception(implode(', ', $errors));
    }
  }
}