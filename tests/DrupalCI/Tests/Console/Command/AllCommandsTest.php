<?php

namespace DrupalCI\Tests\Console\Command;

use Symfony\Component\Console\Command\Command;

/**
 * Tests dealing with all of the expected commands.
 *
 * @group Command
 */
class AllCommandsPresentTest extends CommandTestBase {

  public function provideCommandNames() {
    return [
      ['run'],
    ];
  }

  /**
   * Verify that we can find all commands on the app object.
   *
   * @coversNothing
   * @dataProvider provideCommandNames
   *
   * @param $command_name
   */
  public function testAllCommandsPresent($command_name) {
    $c = $this->getConsoleApp();
    // find() throws an exception if the name can't be found.
    $command = $c->find($command_name);
    $this->assertInstanceOf(Command::class, $command);
  }

}
