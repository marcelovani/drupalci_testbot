<?php

namespace DrupalCI\Build\Codebase;

use DrupalCI\Injectable;
use Pimple\Container;

/**
 * Class Patch
 * @package DrupalCI\Build\Codebase
 */
class Patch implements PatchInterface, Injectable {

  /**
   * Style object.
   *
   * @var \DrupalCI\Console\DrupalCIStyle
   */
  protected $io;

  /**
   * The url of the patch
   *
   * @var string
   */
  protected $url;

  /**
   * Base Working directory
   *
   * @var string
   */
  protected $working_dir;

  /**
   * Target patch application directory
   *
   * @var string
   */
  protected $targetApplyDir;

  /**
   * Source patch filename
   * @var string
   */
  protected $filename;

  /**
   * Source patch location on the local file system
   *
   * @var string
   */
  protected $absolutePath;

  /**
   * "Patch has been applied" flag
   *
   * @var bool
   */
  protected $applied;

  /**
   * List of files modified by this patch
   *
   * @var array
   */
  protected $modified_files;

  /**
   * Results from applying a patch
   *
   * @var string
   */
  protected $patch_apply_results;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  public function inject(Container $container) {
    $this->io = $container['console.io'];
    $this->httpClient = $container['http.client'];
  }

  /**
   * @return string
   */
  public function getFilename() {
    return $this->filename;
  }

  /**
   * @param string $source
   */
  protected function setPatchFileName($source) {
    $this->filename = $source;
  }

  /**
   * @return string
   */
  public function getTargetApplyDir() {
    return $this->targetApplyDir;
  }

  /**
   * @param string $targetApplyDir
   */
  protected function setTargetApplyDir($targetApplyDir) {
    $this->targetApplyDir = $targetApplyDir;
  }

  /**
   * @return string[]
   */
  public function getPatchApplyResults() {
    return $this->patch_apply_results;
  }

  /**
   * @param string[] $patch_apply_results
   */
  public function setPatchApplyResults($patch_apply_results) {
    $this->patch_apply_results = $patch_apply_results;
  }

  /**
   * @param string[] $patch_details
   * @param string $ancillary_workspace The real working directory.
   */
  public function __construct($patch_details, $ancillary_workspace) {
    // Copy working directory from the initial codebase

    $this->working_dir = $ancillary_workspace;

    $this->targetApplyDir = $patch_details['to'];

    // Determine whether passed a URL or local file
    $type = filter_var($patch_details['from'], FILTER_VALIDATE_URL) ? "remote" : "local";

    // If a remote file, download a local copy
    if ($type == "remote") {
      // Download the patch file
      // If any errors encountered during download, we expect guzzle to throw
      // an appropriate exception.
      $this->url = $patch_details['from'];
      $absolute_path = $this->download();
    }
    else {
      // If its not a url, its a filepath. If the filepath is absolute already,
      // Then its likely a local developer pointing at a locally crafted patch.
      if (strpos($patch_details['from'], '/') === 0) {
        $absolute_path = $patch_details['from'];
        $this->filename = basename($patch_details['from']);
      }
      else {
        $absolute_path = $ancillary_workspace . '/' . basename($patch_details['from']);
      }
      $this->filename = basename($patch_details['from']);

    }
    $this->absolutePath = $absolute_path;

    // Set initial 'applied' state
    $this->applied = FALSE;

  }

  /**
   * Obtain remote patch file
   *
   * @return string
   */
  protected function download() {
    $url = $this->url;
    $file_info = pathinfo($url);
    $directory = $this->working_dir;
    $destination_file = $directory . '/' . $file_info['basename'];
    $this->httpClient
      ->get($url, ['save_to' => "$destination_file"]);
    $this->io->writeln("<info>Patch downloaded to <options=bold>$destination_file</></info>");
    $this->setPatchFileName($file_info['basename']);
    return $destination_file;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    if ($this->validate_file() && $this->validate_target()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Validate file exists
   *
   * @return bool
   */
  public function validate_file() {
    $source = $this->absolutePath;
    $real_file = realpath($source);
    if ($real_file === FALSE) {
      // Invalid patch file
      $this->io->drupalCIError("Patch Error", "The patch file <info>$source</info> is invalid.");
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Validate target directory exists
   *
   * @return bool
   */
  public function validate_target() {
    $apply_dir = $this->targetApplyDir;
    $real_directory = realpath($apply_dir);
    if ($real_directory === FALSE) {
      // Invalid target directory
      $this->io->drupalCIError("Patch Error", "The target patch directory <info>$apply_dir</info> is invalid.");
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Apply the patch
   *
   * @return bool
   */
  public function apply() {

    $absolutePath = $this->absolutePath;
    $target = $this->targetApplyDir;

    $this->io->writeln("Applying patch $absolutePath to $target");

    $cmd = "cd $target && sudo -u www-data git apply -p1 $absolutePath 2>&1";

    exec($cmd, $cmdoutput, $result);
    $this->setPatchApplyResults($cmdoutput);
    if ($result !== 0) {
      // The command threw an error.
      $this->io->writeLn($cmdoutput);
      $this->io->drupalCIError("Patch Error", "The patch attempt returned an error.  Error code: $result");
      // TODO: Pass on the actual return value for the patch attempt
      return $result;
    }
    $this->io->writeLn("<comment>Patch <options=bold>$absolutePath</> applied to directory <options=bold>$target</></comment>");
    $this->applied = TRUE;
    return $result;
  }

  /**
   * Retrieves the files modified by this patch
   *
   * @return array|bool
   */
  public function getModifiedFiles() {
    // Only calculate the modified files if the patch has been applied.
    if (!$this->applied) {
      return [];
    }
    if (empty($this->modified_files)) {
      // Calculate modified files

      $target = $this->getTargetApplyDir();
      // TODO: refactor this exec out of here.
      $cmd = "cd $target && git ls-files --other --modified --exclude-standard --exclude=vendor";
      exec($cmd, $cmdoutput, $return);
      if ($return !== 0) {
        // git diff returned a non-zero error code
        $this->io->writeln("<error>Git diff command returned a non-zero code while attempting to parse modified files. (Return Code: $return)</error>");
        return FALSE;
      }
      $files = $cmdoutput;

      $this->modified_files = array();
      foreach ($files as $file) {
        $this->modified_files[] = realpath($this->getTargetApplyDir()) . '/' . $file;
      }
    }
    return $this->modified_files;
  }

}
