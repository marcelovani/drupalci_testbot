<?php

namespace DrupalCI\Build\Artifact;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Environment\EnvironmentInterface;
use Symfony\Component\Filesystem\Filesystem;

class ContainerBuildArtifact extends BuildArtifact {

  /* @var  \DrupalCI\Build\Environment\EnvironmentInterface */
  protected $environment;

  public function __construct(EnvironmentInterface $environment, BuildInterface $build, $path, $artifactpath = '') {
    $this->environment = $environment;
    parent::__construct($build, $path, $artifactpath);
  }

  /**
   * @inheritDoc
   */
  public function preserve() {
    $uid = posix_getuid();
    $gid = posix_getgid();

    $artifactPath = $this->environment->getContainerArtifactDir() . '/' . $this->artifactPath;


    if (strpos($this->sourcePath, $this->environment->getContainerArtifactDir()) === FALSE) {

      // Check if the sourcepath is a directory
      $commands = [
        '[ -d "'. $this->sourcePath .'" ]'
        ];
      $result = $this->environment->executeCommands($commands);
      if ($result->getSignal() == 0) {
        // Source path is a directory.
        $commands = [
          'mkdir -p ' . $artifactPath,
          'cp -R ' . $this->sourcePath . '/* ' . $artifactPath,
        ];
      } else {
        $info = pathinfo($artifactPath);
        $commands = [
          'mkdir -p ' . $info['dirname'],
          'cp -R ' . $this->sourcePath . ' ' . $artifactPath,
        ];
      }
      $result = $this->environment->executeCommands($commands);

    }
    $commands = [
      'chown -R ' . $uid . ':' . $gid . ' ' . $artifactPath,
    ];
    $result = $this->environment->executeCommands($commands);
  }

}
