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
  public function setResultLabel($resultLabel) {
    $this->resultLabel = $resultLabel;
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
  public function setResultDetails($resultDetails) {
    $this->resultDetails = $resultDetails;
  }

  /**
   * @inheritDoc
   */
  public function serializeResults() {
    $results = ['buildLabel' => $this->resultLabel, 'buildDetails' => $this->resultDetails];
    return  json_encode($results);
  }

}
