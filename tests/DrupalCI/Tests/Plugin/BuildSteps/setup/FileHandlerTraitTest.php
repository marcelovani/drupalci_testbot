<?php

namespace DrupalCI\Tests\Plugin\BuildSteps\setup;

use DrupalCI\Plugin\BuildTask\FileHandlerTrait;

/**
 * @file
 * contains \DrupalCI\Tests\Plugin\Preprocess\definition\FetchDefinitionPreprocessorTest
 *
 * @group Plugin
 * @group DefinitionPreprocessor
 *
 * @coversDefaultClass \DrupalCI\Plugin\BuildTask\FileHandlerTrait
 */

class FileHandlerTraitTest extends \PHPUnit_Framework_TestCase {

  /**
   * @param string $file_definition      The value passed into the patch command
   * @param array  $expected_result   Expected Result
   *
   * @dataProvider provideTestProcess
   * @covers ::process
   */
  public function testProcess($file_definition, $expected_result) {
    $handler = $this->getMockForTrait(FileHandlerTrait::class);

    $ref_process = new \ReflectionMethod($handler, 'process');
    $ref_process->setAccessible(TRUE);

    $this->assertEquals($expected_result, $ref_process->invoke($handler, $file_definition));
  }

  public function provideTestProcess() {
    return [
      // Fetch data.
      // Test single fetch with no directory specified
      ['http://example.com/file1.patch', [['from' => 'http://example.com/file1.patch', 'to' => '.']]],
      // Test single fetch with directory specified
      ['http://example.com/file1.patch,dir1', [['from' => 'http://example.com/file1.patch', 'to' => 'dir1']]],
      // Test multiple fetches with no directory specified
      ['http://example.com/file1.patch;http://example.com/file2.patch', [['from' => 'http://example.com/file1.patch', 'to' => '.'],['from' => 'http://example.com/file2.patch', 'to' => '.']]],
      // Test multiple fetches with some directories specified
      ['http://example.com/file1.patch,dir1;http://example.com/file2.patch', [['from' => 'http://example.com/file1.patch', 'to' => 'dir1'],['from' => 'http://example.com/file2.patch', 'to' => '.']]],
      ['http://example.com/file1.patch;http://example.com/file2.patch,dir2', [['from' => 'http://example.com/file1.patch', 'to' => '.'],['from' => 'http://example.com/file2.patch', 'to' => 'dir2']]],
      // Test multiple fetches with all directories specified
      ['http://example.com/file1.patch,dir1;http://example.com/file2.patch,dir2', [['from' => 'http://example.com/file1.patch', 'to' => 'dir1'],['from' => 'http://example.com/file2.patch', 'to' => 'dir2']]],
      // Test single fetch with trailing comma
      ['http://example.com/file1.patch,', [['from' => 'http://example.com/file1.patch', 'to' => '.']]],
      // Test single fetch with trailing semicolon
      ['http://example.com/file1.patch;', [['from' => 'http://example.com/file1.patch', 'to' => '.']]],
      // Patch data.
      // Test single patch with no directory specified
      ['file1.patch', [['from' => 'file1.patch', 'to' => '.']]],
      // Test single patch with directory specified
      ['file1.patch,dir1', [['from' => 'file1.patch', 'to' => 'dir1']]],
      // Test multiple patches with no directory specified
      ['file1.patch;file2.patch', [['from' => 'file1.patch', 'to' => '.'],['from' => 'file2.patch', 'to' => '.']]],
      // Test multiple patches with some directories specified
      ['file1.patch,dir1;file2.patch', [['from' => 'file1.patch', 'to' => 'dir1'],['from' => 'file2.patch', 'to' => '.']]],
      ['file1.patch;file2.patch,dir2', [['from' => 'file1.patch', 'to' => '.'],['from' => 'file2.patch', 'to' => 'dir2']]],
      // Test multiple patches with all directories specified
      ['file1.patch,dir1;file2.patch,dir2', [['from' => 'file1.patch', 'to' => 'dir1'],['from' => 'file2.patch', 'to' => 'dir2']]],
      // Test single patch with trailing comma
      ['file1.patch,', [['from' => 'file1.patch', 'to' => '.']]],
      // Test single patch with trailing semicolon
      ['file1.patch;', [['from' => 'file1.patch', 'to' => '.']]],
    ];
  }

}
