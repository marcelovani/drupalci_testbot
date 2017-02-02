<?php
namespace DrupalCI\Build\Environment;

use Pimple\Container;

interface EnvironmentInterface {

  public function inject(Container $container);

  /**
   * @param $commands
   *
   * @param null $container_id
   *
   * @return \DrupalCI\Build\Environment\CommandResultInterface
   *
   * Takes in an array of commands to execute on a container and returns a
   * CommandResult object with the signal, stdout, and stderr. Optional
   * container_id allows for a specific container to be selected.
   */
  public function executeCommands($commands, $container_id = NULL);

  public function startExecContainer($container);

  public function startServiceContainerDaemons($container);

  public function terminateContainers();

  public function getDatabaseContainer();

  public function getExecContainer();

  /**
   * @return string
   *   The source directory mounted within the container.
   */
  public function getExecContainerSourceDir();

  /**
   * @return string
   *   The artifact directory on all containers
   */
  public function getContainerArtifactDir();

  /**
   * @return string
   *   The composer cache directory inside the containers.
   */
  public function getContainerComposerCacheDir();

}
