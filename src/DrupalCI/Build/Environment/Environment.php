<?php

namespace DrupalCI\Build\Environment;

use Docker\API\Model\ContainerConfig;
use Docker\API\Model\CreateImageInfo;
use Docker\API\Model\ExecConfig;
use Docker\API\Model\ExecStartConfig;
use Docker\API\Model\HostConfig;
use Docker\Manager\ExecManager;
use DrupalCI\Injectable;
use Pimple\Container;

class Environment implements Injectable, EnvironmentInterface {

  /**
   * @var \DrupalCI\Console\DrupalCIStyle
   */
  protected $io;

  /**
   * Stores our Docker Container manager
   *
   * @var \Docker\Docker
   */
  protected $docker;

  // Holds the name and Docker IDs of our executable container.
  protected $executableContainer = [];

  // Holds the name and Docker IDs of our service container.
  protected $databaseContainer;

  /* @var DatabaseInterface */
  protected $database;

  /* @var \DrupalCI\Build\BuildInterface */
  protected $build;

  /* @var \Pimple\Container */
  protected $container;

  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;

  /**
   * @var string
   * The source directory within the exec container.
   */
  protected $execContainerSourceDir = '/var/www/html';

  /**
   * @var string
   * Build directory for artifacts exposed within the container.
   */
  protected $containerArtifactDir = '/var/lib/drupalci/artifacts';

  /**
   * @var string
   * Work directory exposed within the container.
   */
  protected $containerWorkDir = '/var/lib/drupalci/workdir';

  /**
   * @var string
   * This *must* match the /proc/sys/kernel/core_pattern of the docker host
   */
  protected $containerCoreDumpDir = '/var/lib/drupalci/coredumps';

  /**
   * @var string
   *
   * Directory for composer cahces
   */
  protected $containerComposerCacheDir = '/root/.composer/cache';

  public function inject(Container $container) {

    $this->io = $container['console.io'];
    $this->docker = $container['docker'];
    $this->codebase = $container['codebase'];
    $this->database = $container['db.system'];
    $this->build = $container['build'];
    $this->container = $container;

  }

  /**
   * {@inheritdoc}
   */
  public function executeCommands($commands, $container_id = '') {

    $executionResult = $this->container['command.result'];
    $maxExitCode = 0;
    // Data format: 'command [arguments]' or array('command [arguments]', 'command [arguments]')
    // $data May be a string if one version required, or array if multiple
    // Normalize data to the array format, if necessary
    $commands = is_array($commands) ? $commands : [$commands];
    if (!empty($commands)) {
      if (!empty($container_id)) {
        $id = $container_id;
      }
      else {
        $container = $this->getExecContainer();
        if (!empty($container)) {
          $id = $container['id'];
        }
        else {
          // No existing container to run commands on.
          return 1;
        }
      }

      $short_id = substr($id, 0, 8);
      // TODO: add a way to add debugging information, and make this a debug
      // ouput
      //$this->io->writeLn("<info>Executing on container instance $short_id:</info>");
      foreach ($commands as $cmd) {
        $this->io->writeLn("<fg=magenta>$cmd</fg=magenta>");
        $executionResult->appendOutput("\nEXECUTING: " . $cmd . "\n");
        $executionResult->appendError("\nEXECUTING: " . $cmd . "\n");
        $exec_config = new ExecConfig();
        $exec_config->setTty(FALSE);
        $exec_config->setAttachStderr(TRUE);
        $exec_config->setAttachStdout(TRUE);
        $exec_config->setAttachStdin(FALSE);
        $command = ["/bin/bash", "-c", $cmd];
        $exec_config->setCmd($command);

        $exec_manager = $this->docker->getExecManager();
        $response = $exec_manager->create($id, $exec_config);

        $exec_id = $response->getId();
        // $this->io->writeLn("<info>Command created as exec id " . substr($exec_id, 0, 8) . "</info>");

        $exec_start_config = new ExecStartConfig();
        $exec_start_config->setTty(FALSE);
        $exec_start_config->setDetach(FALSE);

        $stream = $exec_manager->start($exec_id, $exec_start_config, [], ExecManager::FETCH_STREAM);

        $stdoutFull = "";
        $stderrFull = "";
        $stream->onStdout(function ($stdout) use (&$stdoutFull) {
          $stdoutFull .= $stdout;
          $this->io->write($stdout);
        });
        $stream->onStderr(function ($stderr) use (&$stderrFull) {
          $stderrFull .= $stderr;
          $this->io->write($stderr);
        });
        $stream->wait();
        $exit_code = $exec_manager->find($exec_id)->getExitCode();
        $maxExitCode = max($exit_code, $maxExitCode);
        $executionResult->appendOutput($stdoutFull);
        $executionResult->appendError($stderrFull);
      }
      $executionResult->setSignal($maxExitCode);
    }
    return $executionResult;
  }

  /**
   * @return mixed
   */
  public function getDatabaseContainer() {
    return $this->databaseContainer;
  }

  public function getExecContainer() {
    return $this->executableContainer;
  }

  /**
   * @return string
   */
  public function getExecContainerSourceDir() {
    return $this->execContainerSourceDir;
  }

  /**
   * @return string
   * TODO 2851000 - convert all of these to WorkDir
   */
  public function getContainerArtifactDir() {
    return $this->containerArtifactDir;
  }

  /**
   * @return string
   */
  public function getContainerWorkDir() {
    return $this->containerWorkDir;
  }

  /**
   * @return string
   */
  public function getContainerComposerCacheDir() {
    return $this->containerComposerCacheDir;
  }

  /**
   * @param array $container
   */
  public function startExecContainer($container) {

    // Map working directory
    $container['HostConfig']['Binds'][] = $this->codebase->getSourceDirectory() . ':' . $this->execContainerSourceDir;
    $container['HostConfig']['Binds'][] = $this->build->getArtifactDirectory() . ':' . $this->containerArtifactDir;
    $container['HostConfig']['Binds'][] = $this->build->getAncillaryWorkDirectory() . ':' . $this->containerWorkDir;
    $container['HostConfig']['Binds'][] = $this->build->getHostCoredumpDirectory() . ':' . $this->containerCoreDumpDir;
    $container['HostConfig']['Binds'][] = $this->build->getHostComposerCacheDirectory() . ':' . $this->containerComposerCacheDir;
    $container['HostConfig']['Ulimits'][] = ['Name' => 'core', 'Soft' => -1, 'Hard' => -1 ];
    $this->executableContainer = $this->startContainer($container);

  }

  public function startServiceContainerDaemons($db_container) {
    if (strpos($this->database->getDbType(), 'sqlite') === 0) {
      return;
    }
    $db_container['HostConfig']['Binds'][0] = $this->build->getDBDirectory() . ':' . $this->database->getDataDir();
    $db_container['HostConfig']['Binds'][] = $this->build->getHostCoredumpDirectory() . ':' . $this->containerCoreDumpDir;
    $db_container['HostConfig']['Ulimits'][] = ['Name' => 'core', 'Soft' => -1, 'Hard' => -1 ];

    $this->databaseContainer = $this->startContainer($db_container);
    $this->database->setHost($this->databaseContainer['ip']);

  }

  public function terminateContainers() {

    $manager = $this->docker->getContainerManager();

    if (!empty($this->executableContainer['id'])) {
      // Kill the containers we started.
      $manager->remove($this->executableContainer['id'], ['force' => TRUE]);
    }
    if (($this->database->getDbType() !== 'sqlite') && (!empty($this->databaseContainer['id']))) {
      $manager->remove($this->databaseContainer['id'], ['force' => TRUE]);
    }
  }

  protected function startContainer($config) {

    $this->pull($config['Image']);
    $manager = $this->docker->getContainerManager();
    $container_config = new ContainerConfig();
    $container_config->setImage($config['Image']);
    $host_config = new HostConfig();
    $host_config->setBinds($config['HostConfig']['Binds']);
    $host_config->setUlimits($config['HostConfig']['Ulimits']);
    $container_config->setHostConfig($host_config);
    $parameters = [];
    $create_result = $manager->create($container_config, $parameters);
    $container_id = $create_result->getId();

    $response = $manager->start($container_id);
    // TODO: Throw exception if doesn't return 204.

    $executable_container = $manager->find($container_id);

    $container['id'] = $executable_container->getID();
    $container['name'] = $executable_container->getName();
    $container['ip'] = $executable_container->getNetworkSettings()
      ->getIPAddress();
    $container['image'] = $config['Image'];

    $short_id = substr($container['id'], 0, 8);
    $this->io->writeln("<comment>Container <options=bold>${container['name']}</> created from image <options=bold>${config['Image']}</> with ID <options=bold>$short_id</></comment>");

    return $container;
  }

  /**
   * (#inheritdoc)
   *
   * @param $name
   */
  protected function pull($name) {
    $manager = $this->docker->getImageManager();
    $progressInformation = NULL;
    $image_name = explode(':', $name);
    if (empty($image_name[1])) {
      $image_name[1] = 'latest';
    }
    $response = $manager->create('', ['fromImage' => $image_name[0] . ':' . $image_name[1]], $manager::FETCH_STREAM);

    //$response->onFrame(function (CreateImageInfo $createImageInfo) use (&$progressInformation) {
    $response->onFrame(function (CreateImageInfo $createImageInfo) use (&$progressInformation) {
      $createImageInfoList[] = $createImageInfo;
      if ($createImageInfo->getStatus() === "Downloading") {
        $progress = $createImageInfo->getProgress();
        preg_match("/\]\s+(?P<current>(?:[0-9\.]+)?)\s[kM]*B\/(?P<total>(?:[0-9\.]+)?)\s/", $progress, $status);
        // OPUT
        //        $progressbar = new ProgressBar($this->io, $status['total']);
        //        $progressbar->start();
        //        $progressbar->advance($status['current']);
      }
      else {
        $this->io->writeln("<comment>" . $createImageInfo->getStatus() . "</comment>");
      }
    });
    $response->wait();

    $this->io->writeln("");
  }

}
