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
class CoreCoderPhpcsConfigModifiedTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout',
    'DCI_LocalCommitHash=4b65a2b',
    'DCI_DBType=sqlite',
    'DCI_PHPVersion=7',
    'DCI_DEBUG=TRUE',
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
    $this->assertNotRegExp('/Running PHP Code Sniffer review on modified files./', $app_tester->getDisplay());
    $this->assertRegExp('/PHPCS config file modified, sniffing entire project./', $app_tester->getDisplay());
    $this->assertRegExp('`Sniffing all files starting at core/.`', $app_tester->getDisplay());
    // Commit hash 4b65a2b always fails.
    $this->assertEquals(1, $app_tester->getStatusCode());
  }
}
