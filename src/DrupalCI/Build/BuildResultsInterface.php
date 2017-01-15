<?php

namespace DrupalCI\Build;


interface BuildResultsInterface {
  /**
   * @return mixed
   *
   * Returns the short 50 character description that is used in d.o.'s UI
   */
  public function getResultLabel();

  /**
   * @param mixed $resultLabel
   */
  public function setResultLabel($resultLabel);

  /**
   * @return mixed
   *
   * Returns the comprehensive details about the build results. This will
   * usually only be relevant when the build fails, as successful builds
   * typically have a variety of result details that are emitted.
   */
  public function getResultDetails();

  /**
   * @param mixed $resultDetails
   */
  public function setResultDetails($resultDetails);

  /**
   * @return string
   *
   * Returns a serialized json representation of the build results.
   */
  public function serializeResults();
}
