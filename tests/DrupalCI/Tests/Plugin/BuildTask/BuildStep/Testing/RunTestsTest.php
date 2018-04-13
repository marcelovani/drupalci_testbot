<?php

namespace DrupalCI\Tests\Plugin\BuildTask\BuildStep\Testing;

use DrupalCI\Build\Codebase\CodebaseInterface;
use DrupalCI\Build\Environment\CommandResult;
use DrupalCI\Build\Environment\CommandResultInterface;
use DrupalCI\Build\Environment\DatabaseInterface;
use DrupalCI\Build\Environment\EnvironmentInterface;
use DrupalCI\Plugin\BuildTask\BuildStep\Testing\RunTests;
use DrupalCI\Plugin\BuildTask\BuildStep\Testing\RunTestsD7;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use DrupalCI\Plugin\BuildTask\HaltingFailException;
use DrupalCI\Tests\DrupalCITestCase;

/**
 * @group RunTests
 * @coversDefaultClass \DrupalCI\Plugin\BuildTask\BuildStep\Testing\RunTests
 */
class RunTestsTest extends DrupalCITestCase {

  public function providerGetRunTestsCommand() {
    return [
      'core' => [
        'cd exec-container-source-dir && sudo MINK_DRIVER_ARGS_WEBDRIVER=\'["chrome", {"browserName":"chrome","chromeOptions":{"args":["--disable-gpu","--headless"]}}, "http://chromecontainer-host:9515"]\' -u www-data php exec-container-source-dir/core/scripts/run-tests.sh --color --keep-results --values=value --all',
        'core',
      ],
      'contrib-default' => [
        'cd exec-container-source-dir && sudo MINK_DRIVER_ARGS_WEBDRIVER=\'["chrome", {"browserName":"chrome","chromeOptions":{"args":["--disable-gpu","--headless"]}}, "http://chromecontainer-host:9515"]\' -u www-data php exec-container-source-dir/core/scripts/run-tests.sh --color --keep-results --suppress-deprecations --values=value --directory true-extension-subdirectory',
        'contrib',
      ],
    ];
  }

  public function providerEnvironmentGetRunTestsCommand() {
    return [
      'core' => [
        'cd exec-container-source-dir && sudo MINK_DRIVER_ARGS_WEBDRIVER=\'["chrome", {"browserName":"chrome","chromeOptions":{"args":["--disable-gpu","--headless"]}}, "http://chromecontainer-host:9515"]\' -u www-data php exec-container-source-dir/core/scripts/run-tests.sh --color --die-on-fail --keep-results --keep-results-table --verbose --types "TestTypes" --concurrency "Over9000" --repeat "1001" --url "TheURLOfmyServer" --dburl "databaseURL" --class "\Has\No\Class"',
        'core',
        ['DCI_Concurrency=Over9000','DCI_RTRepeat=1001','DCI_RTTypes=TestTypes','DCI_RTUrl=TheURLOfmyServer','DCI_TestGroups=--class "\Has\No\Class"','DCI_RTColor=TRUE','DCI_RTDieOnFail=TRUE','DCI_RTKeepResults=TRUE','DCI_RTKeepResultsTable=TRUE','DCI_RTVerbose=TRUE','DCI_RTSuppressDeprecations=TRUE'],
      ],
      'contrib-with-testgroups' => [
        'cd exec-container-source-dir && sudo MINK_DRIVER_ARGS_WEBDRIVER=\'["chrome", {"browserName":"chrome","chromeOptions":{"args":["--disable-gpu","--headless"]}}, "http://chromecontainer-host:9515"]\' -u www-data php exec-container-source-dir/core/scripts/run-tests.sh --color --die-on-fail --keep-results --keep-results-table --verbose --suppress-deprecations --types "TestTypes" --concurrency "Over9000" --repeat "1001" --url "TheURLOfmyServer" --dburl "databaseURL" --class "\Has\No\Class"',
        'contrib',
        ['DCI_Concurrency=Over9000','DCI_RTRepeat=1001','DCI_RTTypes=TestTypes','DCI_RTUrl=TheURLOfmyServer','DCI_TestGroups=--class "\Has\No\Class"','DCI_RTColor=TRUE','DCI_RTDieOnFail=TRUE','DCI_RTKeepResults=TRUE','DCI_RTKeepResultsTable=TRUE','DCI_RTVerbose=TRUE','DCI_RTSuppressDeprecations=TRUE'],
      ],
      'contrib-default' => [
        'cd exec-container-source-dir && sudo MINK_DRIVER_ARGS_WEBDRIVER=\'["chrome", {"browserName":"chrome","chromeOptions":{"args":["--disable-gpu","--headless"]}}, "http://chromecontainer-host:9515"]\' -u www-data php exec-container-source-dir/core/scripts/run-tests.sh --color --die-on-fail --keep-results --keep-results-table --verbose --suppress-deprecations --types "TestTypes" --concurrency "Over9000" --repeat "1" --url "TheURLOfmyServer" --dburl "databaseURL" --directory true-extension-subdirectory',
        'contrib',
        ['DCI_Concurrency=Over9000','DCI_RTTypes=TestTypes','DCI_RTUrl=TheURLOfmyServer','DCI_RTColor=TRUE','DCI_RTDieOnFail=TRUE','DCI_RTKeepResults=TRUE','DCI_RTKeepResultsTable=TRUE','DCI_RTVerbose=TRUE','DCI_RTSuppressDeprecations=TRUE'],
      ],
      'contrib-suppress' => [
        'cd exec-container-source-dir && sudo MINK_DRIVER_ARGS_WEBDRIVER=\'["chrome", {"browserName":"chrome","chromeOptions":{"args":["--disable-gpu","--headless"]}}, "http://chromecontainer-host:9515"]\' -u www-data php exec-container-source-dir/core/scripts/run-tests.sh --color --die-on-fail --keep-results --keep-results-table --verbose --types "TestTypes" --concurrency "Over9000" --repeat "1" --url "TheURLOfmyServer" --dburl "databaseURL" --directory true-extension-subdirectory',
        'contrib',
        ['DCI_Concurrency=Over9000','DCI_RTTypes=TestTypes','DCI_RTUrl=TheURLOfmyServer','DCI_RTColor=TRUE','DCI_RTDieOnFail=TRUE','DCI_RTKeepResults=TRUE','DCI_RTKeepResultsTable=TRUE','DCI_RTVerbose=TRUE','DCI_RTSuppressDeprecations=FALSE'],
      ],
      'core-no-flags' => [
        'cd exec-container-source-dir && sudo MINK_DRIVER_ARGS_WEBDRIVER=\'["chrome", {"browserName":"chrome","chromeOptions":{"args":["--disable-gpu","--headless"]}}, "http://chromecontainer-host:9515"]\' -u www-data php exec-container-source-dir/core/scripts/run-tests.sh  --types "TestTypes" --concurrency "Over9000" --repeat "1" --url "TheURLOfmyServer" --dburl "databaseURL" --all',
        'core',
        ['DCI_Concurrency=Over9000','DCI_RTTypes=TestTypes','DCI_RTUrl=TheURLOfmyServer','DCI_RTColor=FALSE','DCI_RTDieOnFail=FALSE','DCI_RTKeepResults=FALSE','DCI_RTKeepResultsTable=FALSE','DCI_RTVerbose=FALSE','DCI_RTSuppressDeprecations=FALSE'],
      ],
    ];
  }

  public function providerLegacyEnvironmentGetRunTestsCommand() {
    return [
      'core' => [
        'cd exec-container-source-dir && sudo MINK_DRIVER_ARGS_WEBDRIVER=\'["chrome", {"browserName":"chrome","chromeOptions":{"args":["--disable-gpu","--headless"]}}, "http://chromecontainer-host:9515"]\' -u www-data php exec-container-source-dir/scripts/run-tests.sh --color --die-on-fail --verbose --concurrency "Over9000" --url "TheURLOfmyServer" --class "\Has\No\Class"',
        'core',
        ['DCI_Concurrency=Over9000','DCI_RTRepeat=1001','DCI_RTTypes=TestTypes','DCI_RTUrl=TheURLOfmyServer','DCI_TestGroups=--class "\Has\No\Class"','DCI_RTColor=TRUE','DCI_RTDieOnFail=TRUE','DCI_RTKeepResults=TRUE','DCI_RTKeepResultsTable=TRUE','DCI_RTVerbose=TRUE','DCI_RTSuppressDeprecations=TRUE'],
      ],
      'contrib-with-testgroups' => [
        'cd exec-container-source-dir && sudo MINK_DRIVER_ARGS_WEBDRIVER=\'["chrome", {"browserName":"chrome","chromeOptions":{"args":["--disable-gpu","--headless"]}}, "http://chromecontainer-host:9515"]\' -u www-data php exec-container-source-dir/scripts/run-tests.sh --color --die-on-fail --verbose --concurrency "Over9000" --url "TheURLOfmyServer" --class "\Has\No\Class"'
,
        'contrib',
        ['DCI_Concurrency=Over9000','DCI_RTRepeat=1001','DCI_RTTypes=TestTypes','DCI_RTUrl=TheURLOfmyServer','DCI_TestGroups=--class "\Has\No\Class"','DCI_RTColor=TRUE','DCI_RTDieOnFail=TRUE','DCI_RTKeepResults=TRUE','DCI_RTKeepResultsTable=TRUE','DCI_RTVerbose=TRUE','DCI_RTSuppressDeprecations=TRUE'],
      ],
      'contrib-default' => [
        'cd exec-container-source-dir && sudo MINK_DRIVER_ARGS_WEBDRIVER=\'["chrome", {"browserName":"chrome","chromeOptions":{"args":["--disable-gpu","--headless"]}}, "http://chromecontainer-host:9515"]\' -u www-data php exec-container-source-dir/scripts/run-tests.sh --color --die-on-fail --verbose --concurrency "Over9000" --url "TheURLOfmyServer" --directory true-extension-subdirectory',
        'contrib',
        ['DCI_Concurrency=Over9000','DCI_RTTypes=TestTypes','DCI_RTUrl=TheURLOfmyServer','DCI_RTColor=TRUE','DCI_RTDieOnFail=TRUE','DCI_RTKeepResults=TRUE','DCI_RTKeepResultsTable=TRUE','DCI_RTVerbose=TRUE','DCI_RTSuppressDeprecations=TRUE'],
      ],
      'contrib-suppress' => [
        'cd exec-container-source-dir && sudo MINK_DRIVER_ARGS_WEBDRIVER=\'["chrome", {"browserName":"chrome","chromeOptions":{"args":["--disable-gpu","--headless"]}}, "http://chromecontainer-host:9515"]\' -u www-data php exec-container-source-dir/scripts/run-tests.sh --color --die-on-fail --verbose --concurrency "Over9000" --url "TheURLOfmyServer" --directory true-extension-subdirectory'
,
        'contrib',
        ['DCI_Concurrency=Over9000','DCI_RTTypes=TestTypes','DCI_RTUrl=TheURLOfmyServer','DCI_RTColor=TRUE','DCI_RTDieOnFail=TRUE','DCI_RTKeepResults=TRUE','DCI_RTKeepResultsTable=TRUE','DCI_RTVerbose=TRUE','DCI_RTSuppressDeprecations=FALSE'],
      ],
      'core-no-flags' => [
        'cd exec-container-source-dir && sudo MINK_DRIVER_ARGS_WEBDRIVER=\'["chrome", {"browserName":"chrome","chromeOptions":{"args":["--disable-gpu","--headless"]}}, "http://chromecontainer-host:9515"]\' -u www-data php exec-container-source-dir/scripts/run-tests.sh  --concurrency "Over9000" --url "TheURLOfmyServer" --all'
,
        'core',
        ['DCI_Concurrency=Over9000','DCI_RTTypes=TestTypes','DCI_RTUrl=TheURLOfmyServer','DCI_RTColor=FALSE','DCI_RTDieOnFail=FALSE','DCI_RTKeepResults=FALSE','DCI_RTKeepResultsTable=FALSE','DCI_RTVerbose=FALSE','DCI_RTSuppressDeprecations=FALSE'],
      ],
    ];
  }

  /**
   * @dataProvider providerGetRunTestsCommand
   * @covers ::getRunTestsCommand
   *
   * @param $expected
   * @param $project_type
   *
   * @throws \ReflectionException
   */
  public function testGetRunTestsCommand($expected, $project_type) {
    $container = $this->setupPlugin($project_type);

    $runTests = $this->getMockBuilder(RunTests::class)
      ->setMethods([
        'getRunTestsValues',
      ])
      ->getMock();
    $runTests->expects($this->once())
      ->method('getRunTestsValues')
      ->willReturn('--values=value');

    // Use our mocked services.
    $runTests->inject($container);
    $runTests->configure();


    // Run getRunTestsCommand().
    $ref_get_run_tests_command = new \ReflectionMethod($runTests, 'getRunTestsCommand');
    $ref_get_run_tests_command->setAccessible(TRUE);
    $command = $ref_get_run_tests_command->invoke($runTests);
    $this->assertEquals($expected, $command);
  }

  /**
   * @dataProvider providerEnvironmentGetRunTestsCommand
   * @covers ::getRunTestsCommand
   *
   * @param $expected
   * @param $project_type
   * @param $env_vars
   *
   * @throws \ReflectionException
   */
  public function testConfiguration($expected, $project_type, $env_vars) {
    $container = $this->setupPlugin($project_type);
    foreach ($env_vars as $envvar){
      putenv($envvar);
    }

    $runTests = $this->getMockBuilder(RunTests::class)
      ->setMethods([
        'parseGroups',
      ])
      ->getMock();

    // Use our mocked services.
    $runTests->inject($container);
    $runTests->configure();

    // Run getRunTestsCommand().
    $ref_get_run_tests_command = new \ReflectionMethod($runTests, 'getRunTestsCommand');
    $ref_get_run_tests_command->setAccessible(TRUE);
    $command = $ref_get_run_tests_command->invoke($runTests);
    $this->assertEquals($expected, $command);
      foreach ($env_vars as $variable) {
        list($env_var, $value) = explode('=', $variable);
        putenv($env_var);
      }
  }

  /**
   * @dataProvider providerLegacyEnvironmentGetRunTestsCommand
   * @covers ::getRunTestsCommand
   *
   * @param $expected
   * @param $project_type
   * @param $env_vars
   *
   * @throws \ReflectionException
   */
  public function testLegacyConfiguration($expected, $project_type, $env_vars) {
    $container = $this->setupPlugin($project_type);
    foreach ($env_vars as $envvar){
      putenv($envvar);
    }

    $runTests = $this->getMockBuilder(RunTestsD7::class)
      ->setMethods([
        'parseGroups',
      ])
      ->getMock();

    // Use our mocked services.
    $runTests->inject($container);
    $runTests->configure();

    // Run getRunTestsCommand().
    $ref_get_run_tests_command = new \ReflectionMethod($runTests, 'getRunTestsCommand');
    $ref_get_run_tests_command->setAccessible(TRUE);
    $command = $ref_get_run_tests_command->invoke($runTests);

    foreach ($env_vars as $variable) {
      list($env_var, $value) = explode('=', $variable);
      putenv($env_var);
    }
    $this->assertEquals($expected, $command);
  }

  /**
   * @param $configuration
   *
   * @return \Pimple\Container
   */
  protected function setupPlugin($project_type): \Pimple\Container {
    $command_result = $this->getMockBuilder(CommandResultInterface::class)
      ->setMethods([
        'getSignal',
      ])
      ->getMockForAbstractClass();
    $command_result->expects($this->any())->method('getSignal')->willReturn(0);

    $environment = $this->getMockBuilder(EnvironmentInterface::class)
      ->setMethods([
        'getExecContainerSourceDir',
        'executeCommands',
        'getChromeContainerHostname'
      ])
      ->getMockForAbstractClass();
    $environment->expects($this->any())
      ->method('getExecContainerSourceDir')
      ->willReturn('exec-container-source-dir');
    $environment->expects($this->any())
      ->method('getChromeContainerHostname')
      ->willReturn('chromecontainer-host');
    $environment->expects($this->any())
      ->method('executeCommands')
      ->willReturn($command_result);

    $codebase = $this->getMockBuilder(CodebaseInterface::class)
      ->setMethods(['getProjectSourceDirectory', 'getProjectType'])
      ->getMockForAbstractClass();
    // Always check core for this test.
    $codebase->expects($this->any())
      ->method('getProjectSourceDirectory')
      ->willReturn('true-extension-subdirectory');
    $codebase->expects($this->any())
      ->method('getProjectType')
      ->willReturn($project_type);

    $system_db = $this->getMockBuilder(DatabaseInterface::class)
      ->setMethods(['getUrl', 'getDbType'])
      ->getMockForAbstractClass();
    // Always check core for this test.
    $system_db->expects($this->any())
      ->method('getUrl')
      ->willReturn('databaseURL');
    $system_db->expects($this->any())
      ->method('getDbType')
      ->willReturn('databaseType');

    $container = $this->getContainer([
      'environment' => $environment,
      'codebase' => $codebase,
      'db.system' => $system_db,
    ]);
    return $container;
  }

  public function provideDetermineSignal() {
    return [
      [0, 0, FALSE],
      [0, 1, FALSE],
      [HaltingFailException::class, 1, TRUE],
      [BuildTaskException::class, 2, FALSE],
      [BuildTaskException::class, 3, TRUE],
      [BuildTaskException::class, 4, FALSE],
      [BuildTaskException::class, 255, TRUE],
    ];
  }

  /**
   * @covers ::determineSignal
   * @dataProvider provideDetermineSignal
   */
  public function testDetermineSignal($expected, $signal, $halt_on_fail) {
    $result = new CommandResult();
    $result->setSignal($signal);

    $configuration = [
      'halt-on-fail' => $halt_on_fail,
    ];

    $plugin_factory = $this->getContainer()['plugin.manager.factory']->create('BuildTask');
    $run_tests = $plugin_factory->getPlugin('BuildStep', 'run_tests', $configuration);

    $ref_determine = new \ReflectionMethod($run_tests, 'determineSignal');
    $ref_determine->setAccessible(TRUE);

    if (!is_numeric($expected)) {
      $this->expectException($expected);
      $ref_determine->invokeArgs($run_tests, [$result]);
      return;
    }
    $this->assertEquals($expected, $ref_determine->invokeArgs($run_tests, [$result]));
  }

}
