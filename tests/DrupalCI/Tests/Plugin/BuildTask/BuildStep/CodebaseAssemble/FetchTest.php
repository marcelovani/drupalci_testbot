<?php

namespace DrupalCI\Tests\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Tests\DrupalCITestCase;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use GuzzleHttp\ClientInterface;

/**
 * @coversDefaultClass \DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble\Fetch
 */
class FetchTest extends DrupalCITestCase {

  /**
   * Get a fetch plugin from the factory.
   *
   * @param array $configuration
   *   Configuration to pass to the fetch object.
   *
   * @return \DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble\Fetch
   */
  protected function getFetchPlugin($configuration = []) {
    $plugin_factory = $this->getContainer()['plugin.manager.factory']->create('BuildTask');
    return $plugin_factory->getPlugin('BuildStep', 'fetch', $configuration);
  }

  /**
   * Test behavior when there are no files to fetch.
   *
   * @covers ::run
   */
  public function testRunEmpty() {
    $configuration = ['files' => []];
    $this->assertSame(0, $this->getFetchPlugin($configuration)->run());
  }

  /**
   * Config is present, but 'from' is empty.
   *
   * @covers ::run
   */
  public function testRunNoFrom() {
    $this->expectException(BuildTaskException::class);
    $this->expectExceptionMessage('Fetch error');

    $configuration = [
      'files' => [
        ['from' => '', 'to' => 'to'],
      ],
    ];

    $this->getFetchPlugin($configuration)->run();
  }

  /**
   * Test a common use case for pulling a file over http and storing it.
   *
   * @covers ::run
   */
  public function testRun() {
    $file = 'file.patch';
    $from = 'http://example.com/site/dir/' . $file;
    $to = 'test/dir';

    $configuration = [
      'files' => [
        ['from' => $from, 'to' => $to],
      ],
    ];

    $fetch = $this->getFetchPlugin($configuration);

    // Mock up a Guzzle client.
    $client = $this->getMockBuilder(ClientInterface::class)
      ->setMethods(['get'])
      ->getMockForAbstractClass();
    $client->expects($this->once())
      ->method('get')
      ->with($from, ['save_to' => "/ancillary/$file"]);

    // Poke the mock client into the fetch object.
    $ref_httpclient = new \ReflectionProperty($fetch, 'httpClient');
    $ref_httpclient->setAccessible(TRUE);
    $ref_httpclient->setValue($fetch, $client);

    // Run should return 0 because everything went smoothly.
    $this->assertSame(0, $fetch->run());
  }

  /**
   * Test what happens when Guzzle throws an error.
   *
   * @covers ::run
   */
  public function testRunGuzzleBreaks() {
    $file = 'file.patch';
    $from = 'http://example.com/site/dir/' . $file;
    $to = 'test/dir';

    $this->expectException(BuildTaskException::class);
    $this->expectExceptionMessage('Fetch save error');

    $configuration = [
      'files' => [
        ['from' => $from, 'to' => $to],
      ],
    ];

    $fetch = $this->getFetchPlugin($configuration);

    // Mock up a Guzzle client.
    $client = $this->getMockBuilder(ClientInterface::class)
      ->setMethods(['get'])
      ->getMockForAbstractClass();
    $client->expects($this->once())
      ->method('get')
      ->willThrowException(new \Exception('Guzzle threw this exception'));

    // Poke the mock client into the fetch object.
    $ref_httpclient = new \ReflectionProperty($fetch, 'httpClient');
    $ref_httpclient->setAccessible(TRUE);
    $ref_httpclient->setValue($fetch, $client);

    $fetch->run();
  }

}
