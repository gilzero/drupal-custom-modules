<?php

namespace Drupal\Tests\pdf_gpt_chat\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\pdf_gpt_chat\Service\PdfParserService;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\pdf_gpt_chat\Service\LoggingService;
use Drupal\file\Entity\File;
use Prophecy\Argument;

class PdfParserServiceTest extends UnitTestCase {

  protected $fileSystem;
  protected $cache;
  protected $loggingService;
  protected $pdfParserService;

  protected function setUp(): void {
    parent::setUp();

    $this->fileSystem = $this->prophesize(FileSystemInterface::class);
    $this->cache = $this->prophesize(CacheBackendInterface::class);
    $this->loggingService = $this->prophesize(LoggingService::class);

    $this->pdfParserService = new PdfParserService(
      $this->fileSystem->reveal(),
      $this->cache->reveal(),
      $this->loggingService->reveal()
    );
  }

  public function testExtractTextWithCache() {
    $file = $this->prophesize(File::class);
    $file->id()->willReturn(1);
    $file->getFilename()->willReturn('test.pdf');

    $cachedData = (object) ['data' => 'Cached PDF content'];
    $this->cache->get('pdf_gpt_chat:pdf_content:1')->willReturn($cachedData);

    $result = $this->pdfParserService->extractText($file->reveal());

    $this->assertEquals('Cached PDF content', $result);
  }

  public function testExtractTextWithoutCache() {
    $file = $this->prophesize(File::class);
    $file->id()->willReturn(1);
    $file->getFilename()->willReturn('test.pdf');
    $file->getFileUri()->willReturn('public://test.pdf');

    $this->cache->get('pdf_gpt_chat:pdf_content:1')->willReturn(FALSE);

    // Mock the Smalot\PdfParser\Parser class
    $parser = $this->prophesize(\Smalot\PdfParser\Parser::class);
    $pdf = $this->prophesize(\Smalot\PdfParser\Document::class);
    $parser->parseFile('public://test.pdf')->willReturn($pdf->reveal());
    $pdf->getText()->willReturn('Extracted PDF content');

    // Replace the new Parser() call with our mocked version
    $reflection = new \ReflectionClass($this->pdfParserService);
    $reflection_property = $reflection->getProperty('parser');
    $reflection_property->setAccessible(true);
    $reflection_property->setValue($this->pdfParserService, $parser->reveal());

    $this->cache->set('pdf_gpt_chat:pdf_content:1', 'Extracted PDF content')->shouldBeCalled();

    $result = $this->pdfParserService->extractText($file->reveal());

    $this->assertEquals('Extracted PDF content', $result);
  }

  public function testExtractTextWithParsingError() {
    $file = $this->prophesize(File::class);
    $file->id()->willReturn(1);
    $file->getFilename()->willReturn('test.pdf');
    $file->getFileUri()->willReturn('public://test.pdf');

    $this->cache->get('pdf_gpt_chat:pdf_content:1')->willReturn(FALSE);

    // Mock the Smalot\PdfParser\Parser class to throw an exception
    $parser = $this->prophesize(\Smalot\PdfParser\Parser::class);
    $parser->parseFile('public://test.pdf')->willThrow(new \Exception('PDF parsing error'));

    // Replace the new Parser() call with our mocked version
    $reflection = new \ReflectionClass($this->pdfParserService);
    $reflection_property = $reflection->getProperty('parser');
    $reflection_property->setAccessible(true);
    $reflection_property->setValue($this->pdfParserService, $parser->reveal());

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('PDF parsing error');

    $this->pdfParserService->extractText($file->reveal());
  }
}