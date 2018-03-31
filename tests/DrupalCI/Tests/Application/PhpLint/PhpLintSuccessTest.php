<?php

namespace DrupalCI\Tests\Application\Phpcs;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test what happens when you find a php linting error.
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 * @group phpcs
 *
 * @see TESTING.md
 */
class PhpLintSuccessTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_Fetch=https://www.drupal.org/files/issues/2839170-coder-phpcs-sniff-error.patch,.',
    'DCI_Patch=2839170-coder-phpcs-sniff-error.patch,.',
  ];

  public function testCoderSniffOnlyChangedFailTest() {

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.PhpLint.yml',
    ], $options);
    $this->assertRegExp('/No syntax errors detected/', $this->app_tester->getDisplay());
    $this->assertEquals(0, $this->app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
