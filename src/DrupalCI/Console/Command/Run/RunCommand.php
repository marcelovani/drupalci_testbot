<?php

namespace DrupalCI\Console\Command\Run;

use DrupalCI\Console\Command\Drupal\DrupalCICommandBase;
use DrupalCI\Build\Codebase\Codebase;
use DrupalCI\Build\BuildInterface;
use Pimple\Container;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class RunCommand extends DrupalCICommandBase {

  /**
   * The Build this command is executing.
   *
   * @var \DrupalCI\Build\BuildInterface
   */
  protected $build;

  /**
   * The build task plugin manager.
   *
   * @var \DrupalCI\Plugin\PluginManagerInterface
   */
  protected $buildTaskPluginManager;

  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;

  /**
   * Gets the build from the RunCommand.
   *
   * @return \DrupalCI\Build\BuildInterface
   *   The build being ran.
   */
  public function getBuild() {
    return $this->build;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('run')
      ->setDescription('Execute a given build run.')
      // Argument may be the build type or the path to a specific build definition file
      ->addArgument('definition', InputArgument::OPTIONAL, 'Build definition.');
  }

  protected function initialize(InputInterface $input, OutputInterface $output) {
    parent::initialize($input, $output);
    $this->buildTaskPluginManager = $this->container['plugin.manager.factory']->create('BuildTask');
    $this->build = $this->container['build'];
    $this->codebase = $this->container['codebase'];

  }

  /**
   * {@inheritdoc}
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    // Which build def to use?
    // 1) Command line.
    // 2) Environmental variable.
    // 3) Project drupalci.yml.
    // We always have to perform the bootstrap build.
    $this->io->writeln("<info>Executing common build steps</info>");
    $this->build->generateBuild('bootstrap');
    if ($statuscode = $this->build->executeBuild()) {
      return $statuscode;
    }

//    DCI_UseLocalCodebase=/var/lib/drupalci/drupal-checkout DCI_LocalBranch=8.3.x DCI_LocalCommitHash=c187f1d DCI_TestItem=Url DCI_PHPVersion=php-7.0-apache:production DCI_DBType=mysql DCI_DBVersion=5.5 DCI_CS_SkipCodesniff=TRUE ./drupalci run

    // Gather definition files.
    $project_build_definition = $this->build->getProjectBuildFile();
    $env_build_definition = getenv('DCI_BuildDefinitionFile');
    $cli_build_definition = $input->getArgument('definition');

    // Override definition files based on priority.
    $build_definition_file = $project_build_definition;
    if (!empty($env_build_definition)) {
      $build_definition_file = $env_build_definition;
    }
    if (!empty($cli_build_definition)) {
      $build_definition_file = $cli_build_definition;
    }

    // Generate a new build for the user-space build definition.
    $this->build->generateBuild($build_definition_file);

    $this->io->writeln("<info>Using build definition template: <options=bold>" . $this->build->getBuildFile() . "</options></></info>");

    // Execute the build.
    $statuscode = $this->build->executeBuild();

    return $statuscode;

  }

}
