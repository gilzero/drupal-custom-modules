<?php

namespace Drupal\pdf_gpt_chat\Service;

use Drupal\file\Entity\File;
use Drupal\file\Validation\FileValidatorInterface;

class FileHandlerService {
  protected $fileValidator;

  public function __construct(FileValidatorInterface $file_validator) {
    $this->fileValidator = $file_validator;
  }

  public function validateAndLoadFile($fid) {
    $file = File::load($fid);
    if (!$file) {
      throw new \Exception('Failed to load the uploaded file.');
    }
    $this->validateFileType($file);
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
      throw new \Exception(implode(', ', $errors));
    }
  }
}