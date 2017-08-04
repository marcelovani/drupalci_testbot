<?php

namespace DrupalCI\Tests\Application\Phpcs;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Test that we sniff the entire project if the phpcs config file is modified.
 *
 * NOTE: This test assumes you have followed the setup instructions in TESTING.md
 *
 * @group Application
 * @group phpcs
 *
 * @see TESTING.md
 */
class CoreD8CoderPhpcsConfigModifiedTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout',
    // This commit does not have a phpcs.xml file.
    'DCI_LocalCommitHash=4b65a2b',
    'DCI_DBType=sqlite',
    'DCI_PHPVersion=php-7.0-apache:production',
    'DCI_Fetch=https://www.drupal.org/files/issues/patch_phpcs_xml_dist.patch,.',
    'DCI_Patch=patch_phpcs_xml_dist.patch,.',
    'DCI_CS_SniffOnlyChanged=TRUE',
    'DCI_CS_SniffFailsTest=TRUE',
  ];

  public function testPhpcsConfigModified() {
    $app = $this->getConsoleApp();
    $options = ['interactive' => FALSE];
    $app_tester = new ApplicationTester($app);
    $app_tester->run([
      'command' => 'run',
      'definition' => 'tests/DrupalCI/Tests/Application/Fixtures/build.CoreSniff.yml',
    ], $options);
    $this->assertRegExp('/The installed coding standards are .* Drupal/', $app_tester->getDisplay());
    $this->assertNotRegExp('/Running PHP Code Sniffer review on modified files./', $app_tester->getDisplay());
    $this->assertRegExp('/PHPCS config file modified, sniffing entire project./', $app_tester->getDisplay());
    // Commit hash 4b65a2b always fails CS review.
    $this->assertEquals(1, $app_tester->getStatusCode());
  }

}
