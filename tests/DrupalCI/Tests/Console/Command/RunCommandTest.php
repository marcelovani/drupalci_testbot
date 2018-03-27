<?php

namespace DrupalCI\Tests\Console\Command;

use DrupalCI\Tests\DrupalCIFunctionalTestBase;
use Symfony\Component\Console\Tester\CommandTester;

class RunCommandTest extends DrupalCIFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $dciConfig = [
    'DCI_CoreRepository=file:///var/lib/drupalci/drupal-checkout',
    'DCI_JobType=development',
    'DCI_CS_SkipCodesniff=TRUE',
    'DCI_TestGroups=ban',
  ];

  public function testRun() {
    $c = $this->getConsoleApp();
    $command = $c->find('run');
    $commandTester = new CommandTester($command);
    $commandTester->execute(['command' => $command->getName()]);
    $display = $commandTester->getDisplay(TRUE);
    $this->assertRegExp('`Executing build with build ID:`', $display);
  }

}
