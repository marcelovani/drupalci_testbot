<?php

/**
 * @file
 * Contains \DrupalCI\Tests\Plugin\BuildSteps\setup\FetchTest.
 */

namespace DrupalCI\Tests\Plugin\BuildSteps\setup;

use DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble\Fetch;
use DrupalCI\Tests\DrupalCITestCase;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;
use DrupalCI\Build\Codebase\Codebase;
use DrupalCI\Build\BuildInterface;

/**
 * @coversDefaultClass DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble\Fetch
 */
class FetchTest extends DrupalCITestCase {

  /**
   * @covers ::run
   */
  public function testRun() {
    $file = 'file.patch';
    $url = 'http://example.com/site/dir/' . $file;
    $dir = 'test/dir';

    $request = $this->getMock(RequestInterface::class);

    $http_client = $this->getMock(ClientInterface::class, array('get','send','sendAsync','request','requestAsync','getConfig'));
    $http_client->expects($this->once())
      ->method('get')
      ->with($url, ['save_to' => "$dir/$file"])
      ->will($this->returnValue($request));

    $codebase = $this->getMock(Codebase::class);
    $build = $this->getMockBuilder(BuildInterface::class)
      ->getMockForAbstractClass();

    $data = [
      'files' => [['from' => "$url",'to' => "."]]
    ];
    $fetch = new TestFetch($data);
    $fetch->inject($this->getContainer(['build' => $build]));
    $fetch->setValidate($dir);
    $fetch->setHttpClient($http_client);

    $fetch->run();
  }
}

class TestFetch extends Fetch {
  use TestSetupBaseTrait;

  function setHttpClient(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }
}
