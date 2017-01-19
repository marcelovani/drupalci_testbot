<?php

namespace DrupalCI\Build;

class BuildResults implements BuildResultsInterface {

  protected $resultLabel;

  protected $resultDetails;

  /**
   * BuildResults constructor.
   *
   * @param $resultLabel
   * @param $resultDetails
   */
  public function __construct($resultLabel, $resultDetails = '') {
    // @TODO: Truncate resultLabel to 50 chars
    $this->resultLabel = $resultLabel;
    $this->resultDetails = $resultDetails;
  }

  /**
   * {@inheritdoc}
   */
  public function getResultLabel() {
    return $this->resultLabel;
  }

  /**
   * {@inheritdoc}
   */
  public function getResultDetails() {
    return $this->resultDetails;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    return [
      'buildLabel' => $this->getResultLabel(),
      'buildDetails' => $this->getResultDetails(),
    ];
  }

}
