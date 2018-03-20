<?php

namespace DrupalCI\Build\Environment;

use Docker\API\Model\ContainerConfig;
use Docker\API\Model\ContainersCreatePostBody;
use Docker\API\Model\ContainersIdExecPostBody;
use Docker\API\Model\CreateImageInfo;
use Docker\API\Model\ExecIdStartPostBody;
use Docker\API\Model\HostConfig;
use Docker\API\Model\NetworksCreatePostBody;
use Docker\Docker;
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

  // Holds the name and Docker IDs of our chrome container.
  protected $chromeContainer;

  // This is the docker network that we want to add the containers to.
  protected $dockerNetwork;

  /* @var DatabaseInterface */
  protected $database;

  /* @var \DrupalCI\Build\BuildInterface */
  protected $build;

  /* @var \Pimple\Container */
  protected $container;

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
    $this->database = $container['db.system'];
    $this->build = $container['build'];
    $this->container = $container;

  }

  /**
   * {@inheritdoc}
   */
  public function executeCommands($commands, $container_id = '') {

    /* @var $executionResult \DrupalCI\Build\Environment\CommandResultInterface */
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
          $executionResult->setSignal(1);
          $executionResult->appendOutput('No existing container to run commands on.');
          $executionResult->appendError('No existing container to run commands on.');
          return $executionResult;
        }
      }

      $short_id = substr($id, 0, 8);
      // TODO: add a way to add debugging information, and make this a debug
      // ouput
      //$this->io->writeLn("<info>Executing on container instance $short_id:</info>");
      foreach ($commands as $cmd) {
        $this->io->writeLn("<fg=magenta>$cmd</fg=magenta>");

        $exec_config = new ContainersIdExecPostBody();
        $exec_config->setTty(FALSE);
        $exec_config->setAttachStderr(TRUE);
        $exec_config->setAttachStdout(TRUE);
        $exec_config->setAttachStdin(FALSE);
        $command = ["/bin/bash", "-c", $cmd];
        $exec_config->setCmd($command);

        $response = $this->docker->containerExec($id,$exec_config);

        $exec_id = $response->getId();
        // $this->io->writeLn("<info>Command created as exec id " . substr($exec_id, 0, 8) . "</info>");

        $exec_start_config = new ExecIdStartPostBody();
        $exec_start_config->setTty(FALSE);
        $exec_start_config->setDetach(FALSE);

        $stream = $this->docker->execStart($exec_id, $exec_start_config, [], $this->docker::FETCH_STREAM);

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
        $exit_code = $this->docker->execInspect($exec_id)->getExitCode();
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

  public function getContainerNetwork() {
    return $this->dockerNetwork;
  }

  /**
   * @return string
   */
  public function getExecContainerSourceDir() {
    return $this->execContainerSourceDir;
  }

  /**
   * @return string
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
    $container['Name'] = 'php-apache';
    $container['HostConfig']['Binds'][] = $this->build->getBuildDirectory() . '/source:' . $this->execContainerSourceDir;
    $container['HostConfig']['Binds'][] = $this->build->getArtifactDirectory() . ':' . $this->containerArtifactDir;
    $container['HostConfig']['Binds'][] = $this->build->getAncillaryWorkDirectory() . ':' . $this->containerWorkDir;
    $container['HostConfig']['Binds'][] = $this->build->getHostCoredumpDirectory() . ':' . $this->containerCoreDumpDir;
    $container['HostConfig']['Binds'][] = $this->build->getHostComposerCacheDirectory() . ':' . $this->containerComposerCacheDir;
    $container['HostConfig']['Ulimits'][] = ['Name' => 'core', 'Soft' => -1, 'Hard' => -1 ];
//    #Link this to the chrome container
//    $execname = substr($this->chromeContainer['name'], 1);
//    $container['HostConfig']['Links'][0] = $execname;
    $this->executableContainer = $this->startContainer($container);

  }

  public function startServiceContainerDaemons($db_container) {

    if (strpos($this->database->getDbType(), 'sqlite') === 0) {
      return;
    }
    $db_container['Name'] = 'database';
    $db_container['HostConfig']['Binds'][0] = $this->build->getDBDirectory() . ':' . $this->database->getDataDir();
    $db_container['HostConfig']['Binds'][] = $this->build->getHostCoredumpDirectory() . ':' . $this->containerCoreDumpDir;
    $db_container['HostConfig']['Ulimits'][] = ['Name' => 'core', 'Soft' => -1, 'Hard' => -1 ];

    $this->databaseContainer = $this->startContainer($db_container);
    $this->database->setHost($this->databaseContainer['ip']);

  }

  public function startChromeContainer($chrome_container) {

//    $db_container['HostConfig']['Binds'][0] = $this->build->getDBDirectory() . ':' . $this->database->getDataDir();
//    $db_container['HostConfig']['Binds'][] = $this->build->getHostCoredumpDirectory() . ':' . $this->containerCoreDumpDir;
    $chrome_container['Name'] = 'chromedriver';
    $chrome_container['HostConfig']['Binds'][] = '/dev/shm:/dev/shm';
    $chrome_container['HostConfig']['Ulimits'][] = ['Name' => 'core', 'Soft' => -1, 'Hard' => -1 ];
    $chrome_container['HostConfig']['CapAdd'][] = 'SYS_ADMIN';
    $chrome_container['ExposedPorts'] = new \ArrayObject([ '9515/tcp' => '', '9666/tcp' => '' ]);

    $this->chromeContainer = $this->startContainer($chrome_container);

  }

  public function terminateContainers() {


    if (!empty($this->executableContainer['id'])) {
      // Kill the containers we started.
      $this->docker->containerDelete($this->executableContainer['id'], ['force' => TRUE]);
    }
    if (!empty($this->chromeContainer['id'])) {
      // Kill the containers we started.
      $this->docker->containerDelete($this->chromeContainer['id'], ['force' => TRUE]);
    }
    if (($this->database->getDbType() !== 'sqlite') && (!empty($this->databaseContainer['id']))) {
      $this->docker->containerDelete($this->databaseContainer['id'], ['force' => TRUE]);
    }

  }

  public function createContainerNetwork() {
    // Loop through the existing docker networks so we do not re-create the
    // existing network.
    $networks = $this->docker->networkList();
    foreach ($networks as $docker_network) {
      if ($docker_network->getName() == 'drupalci_nw') {
        $this->dockerNetwork = $docker_network->getId();
        return;
      }
    }

    $container_network = new NetworksCreatePostBody();
    $container_network->setName('drupalci_nw');
    $response = $this->docker->networkCreate($container_network);
    $this->dockerNetwork = $response->getId();
  }

  public function destroyContainerNetwork() {
    $this->docker->networkDelete('drupalci_nw');
  }

  /**
   * @param $config
   *
   * @return mixed
   */
  protected function startContainer($config) {

    $this->pull($config['Image']);

    $container_config = new ContainersCreatePostBody();
    $container_config->setImage($config['Image']);
    $host_config = new HostConfig();
    $host_config->setBinds($config['HostConfig']['Binds']);
    $host_config->setUlimits($config['HostConfig']['Ulimits']);
    if (!empty($config['HostConfig']['CapAdd'])) {
      $host_config->setCapAdd($config['HostConfig']['CapAdd']);
    }
    $host_config->setNetworkMode('drupalci_nw');

    $container_config->setHostConfig($host_config);

    $container_name = $config['Name'] . '-' . $this->build->getBuildId();
    $container_name = preg_replace('/_|\./', "-", $container_name);
    $parameters = ['name' => $container_name];
    $create_result = $this->docker->containerCreate($container_config, $parameters);
    $container_id = $create_result->getId();

    $response = $this->docker->containerStart($container_id);
    // TODO: Throw exception if doesn't return 204.

    $executable_container = $this->docker->containerInspect($container_id);

    $container['id'] = $executable_container->getID();
    $container['name'] = ltrim($executable_container->getName(), '/');
    $networks = $executable_container->getNetworkSettings()->getNetworks();
    foreach ( $networks as $network_name => $network ) {
      if ($network_name == 'drupalci_nw') {
        $container['ip'] = $network->getIPAddress();
      }
    }

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
    $progressInformation = NULL;
    $image_name = explode(':', $name);
    if (empty($image_name[1])) {
      $image_name[1] = 'latest';
    }
    $response = $this->docker->imageCreate('', ['fromImage' => $image_name[0] . ':' . $image_name[1]], [], $this->docker::FETCH_STREAM);

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

  /**
   * We're hacking in the chrome container anyhow, so this is an expediency to
   * get the hostname of it later on.
   * @return mixed
   */
  public function getChromeContainerHostname() {
    return $this->chromeContainer['name'];
  }

  /**
   * @inheritDoc
   */
  public function getHostProcessorCount() {
    $cpuinfo = file_get_contents('/proc/cpuinfo');
    preg_match_all('/^processor/m', $cpuinfo, $matches);
    $numCpus = count($matches[0]);
    return $numCpus;
  }


}
