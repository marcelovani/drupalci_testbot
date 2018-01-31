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
class EsLintFailTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout',
    'DCI_LocalBranch=8.4.x',
    'DCI_DBType=sqlite',
    'DCI_PHPVersion=php-7.1-apache:production',
    'DCI_Fetch=https://www.drupal.org/files/issues/testbot-eslint-test.patch,.',
    'DCI_Patch=testbot-eslint-test.patch,.',
    'DCI_ES_LintFailsTest=TRUE',

  ];

  public function testEslintTest() {

    $options = ['interactive' => FALSE];
    $this->app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.EsLintFailTest.yml',
    ], $options);
    $this->assertRegExp('/Running eslint on modified js files./', $this->app_tester->getDisplay());
    $this->assertEquals(2, $this->app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $this->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Javascript coding standards error');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
