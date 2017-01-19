<?php

namespace DrupalCI\Build;

/**
 * A place to store result information for builds.
 *
 * This object is eventually serialized for JSON, and written to a status file
 * uses by D.O to display to the user.
 */
interface BuildResultsInterface extends \JsonSerializable {

  /**
   * @return mixed
   *
   * Returns the short 50 character description that is used in d.o.'s UI.
   */
  public function getResultLabel();

  /**
   * @return mixed
   *
   * Returns the comprehensive details about the build results. This will
   * usually only be relevant when the build fails, as successful builds
   * typically have a variety of result details that are emitted.
   */
  public function getResultDetails();

}
