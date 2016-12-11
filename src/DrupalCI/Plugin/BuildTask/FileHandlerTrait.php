<?php

namespace DrupalCI\Plugin\BuildTask;

use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTaskBase;
use Pimple\Container;

trait FileHandlerTrait {

  /**
   * Process the DCI_Fetch/DCI_Patch variables.
   *
   * Takes a string defining files to be fetched or applied, and converts this
   * to an array with a from and a to key.
   *
   * Input format: (string) $value = "http://example.com/file1.patch,destination_directory1;[http://example.com/file2.patch,destination_directory2];..."
   * Desired Result: [
   * array('url' => 'http://example.com/file1.patch', 'fetch_directory' => 'fetch_directory1')
   * array('url' => 'http://example.com/file2.patch', 'fetch_directory' => 'fetch_directory2')
   *      ...   ]
   *
   * @param $value
   *
   * @return array
   */
  protected function process($value) {
    $data = [];
    foreach (explode(';', $value) as $file_string) {
      if (!empty($file_string)) {
        $file = [];
        if (strpos($file_string, ',') === FALSE) {
          $file['from'] = $file_string;
          $file['to'] = '.';
        }
        else {
          $elements = explode(',', $file_string);
          $file['from'] = $elements[0];
          $file['to'] = (!empty($elements[1])) ? $elements[1] : '.';
        }
        $data[] = $file;
      }
    }
    return $data;
  }


  protected function validateDirectory($working_dir, $dir) {
    // Validate target directory.  Must be within workingdir.
    $true_dir = realpath($dir);
    if (!empty($true_dir)) {
      if ($true_dir == realpath($working_dir)) {
        // Passed directory is the root working directory.
        return $true_dir;
      }
      // Passed directory is different than working directory. Check whether working directory included in path.
      elseif (strpos($true_dir, realpath($working_dir)) === 0) {
        // Passed directory is an existing subdirectory within the working path.
        return $true_dir;
      }
    }
    // Assume the Passed directory is a subdirectory of the working, without the working prefix.  Construct the full path.
    if (!(strpos($dir, realpath($working_dir)) === 0)) {
      $dir = $working_dir . "/" . $dir;
    }
    $directory = realpath($dir);
    // TODO: Ensure we don't have double slashes
    // Check whether this is a pre-existing directory
    if ($directory === FALSE) {
      // Directory doesn't exist. Create and then validate.
      mkdir($dir, 0777, TRUE);
      $directory = realpath($dir);
    }
    // Validate that resulting directory is still within the working directory path.
    if (!strpos(realpath($directory), realpath($working_dir)) === 0) {
      // Invalid checkout directory
      // TODO Fix This for reals
     // $this->io->drupalCIError("Directory error", "The checkout directory <info>$directory</info> is invalid.");
      return FALSE;
    }

    // Return the updated directory value.
    return $directory;
  }


  protected function getCheckoutDirectory($filehandler) {
    if (isset($filehandler['type']) && ($filehandler['type'] == 'dummy')){
      return $this->codebase->getTmpDirectory();
    }
    return $this->codebase->getSourceDirectory();
  }

  protected function getProjectDirectory($testitem) {
    if (strpos($testitem,'directory') == 0) {
      $components = explode(':', $testitem);
      return $components[1];
    }
    return FALSE;
  }
}
