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

    $arg = $input->getArgument('definition');
    $this->build->generateBuild($arg);

    $this->io->writeln("<info>Using build definition template: <options=bold>" . $this->build->getBuildFile() . "</options></></info>");

    // Execute the build.
    $statuscode = $this->build->executeBuild();

    return $statuscode;

  }

}
