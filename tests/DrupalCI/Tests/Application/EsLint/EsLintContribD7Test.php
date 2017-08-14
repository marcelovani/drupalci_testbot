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
class EsLintD7ContribSuccessTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout',
    'DCI_LocalBranch=7.x',
    'DCI_DBType=sqlite',
    'DCI_PHPVersion=php-5.6-apache:production',
    'DCI_TestItem=directory:modules/metatag',
    'DCI_AdditionalRepositories=git,git://git.drupal.org/project/metatag.git,7.x-1.x#a94f3b25b7740d67ff7d69a4bf18f529c07d6db3,modules/metatag,1;',
    'DCI_TestItem=directory:modules/metatag',
  ];

  public function testEslintContribTest() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.EsLintContribD7Test.yml',
    ], $options);
    $this->assertRegExp('/No modified files. Linting all files./', $app_tester->getDisplay());

    // This shouldnt fail because we're not strict about eslint in d7 contrib
    $this->assertEquals(0, $app_tester->getStatusCode());

    /* @var $build \DrupalCI\Build\BuildInterface */
    $build = $app->getContainer()['build'];
    $this->assertBuildOutputJson($build, 'buildLabel', 'Build Successful');
    $this->assertBuildOutputJson($build, 'buildDetails', '');
  }

}
