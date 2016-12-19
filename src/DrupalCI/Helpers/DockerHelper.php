<?php

/**
 * @file
 * DrupalCI Docker helper class.
 */

namespace DrupalCI\Helpers;

use DrupalCI\Helpers\DrupalCIHelperBase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DockerHelper extends DrupalCIHelperBase {

  /**
   * {@inheritdoc}
   */
  public function locateBinary() {
    $binary = parent::locate_binary('docker');
    return !empty($binary) ? $binary : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isInstalled() {
    return (binary) $this->locateBinary();
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return shell_exec("docker -v");
  }

  /**
   * {@inheritdoc}
   */
  public function printVersion($output) {
    $output->writeln("<comment>Docker version:</comment> " . $this->getVersion());
  }

  /**
   * {@inheritdoc}
   */
  public function getShortVersion() {
    if (preg_match('/[\d]+[\.][\d]+/', $this->getVersion(), $matches)) {
      return $matches[0];
    }
    else {
      // TODO: Throw exception
      return -1;
    }
  }

  public function getStatus(InputInterface $input, OutputInterface $output) {
    $output->writeln("<info>Checking Docker Version ... </info>");
    if ($this->isInstalled()) {
      $this->printVersion($output);
      if (version_compare($this->getShortVersion(), '1.0.0') < 0) {
        $this->minVersionError($output);
      }
    }
    else {
      $this->notFoundError($output);
    }
  }



  /**
   * {@inheritdoc}
   */
  public function notFoundError(OutputInterface $output) {
    $output->writeln("<error>ERROR: Docker not found.</error>");
    $output->writeln("Unable to locate the docker binary.  Has Docker been installed on this host?");
    $output->writeln("If so, please ensure the docker binary location exists on your $PATH, and that the current user has sufficient permissions to run Docker.");
  }

  /**
   * {@inheritdoc}
   */
  public function minVersionError(OutputInterface $output) {
    $output->writeln("<error>ERROR: Obsolete Docker version.</error>");
    $output->writeln("The version of Docker located on this machine does not meet DrupalCI's minimum version requirement.");
    $output->writeln("DrupalCI requires Docker 1.0.0 or greater. Please upgrade Docker.");
  }

}
