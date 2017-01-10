<?php

namespace DrupalCI\Build\Artifact;


use Pimple\Container;
use Symfony\Component\Filesystem\Filesystem;

class ContainerBuildArtifact extends BuildArtifact {

  /* @var  \DrupalCI\Build\Environment\EnvironmentInterface */
  protected $environment;

  public function inject(Container $container) {
    parent::inject($container);
    $this->environment = $container['environment'];
  }

  /**
   * @inheritDoc
   */
  public function preserve() {
    $fs = new Filesystem();
    $uid = posix_getuid();
    $gid = posix_getgid();
    if (strpos($this->sourcePath, $this->environment->getContainerArtifactDir()) === FALSE) {
      $commands = [
        'cp -R ' . $this->sourcePath . ' ' . $this->environment->getContainerArtifactDir(),
      ];
      $result = $this->environment->executeCommands($commands);

    }
    $commands = [
      'chown -R '. $uid . ':' . $gid . ' ' .  $this->environment->getContainerArtifactDir() . "/" . basename($this->sourcePath),
    ];
    $result = $this->environment->executeCommands($commands);
  }
}
