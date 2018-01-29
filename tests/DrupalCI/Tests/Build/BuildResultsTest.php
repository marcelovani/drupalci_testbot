<?php

namespace DrupalCI\Tests\Build;

use DrupalCI\Build\BuildResults;
use PHPUnit\Framework\TestCase;

class BuildResultsTest extends TestCase {

  /**
   * Ensure that JSON contains the keys we think it should.
   */
  public function testJsonRoundTrip() {
    $keys_we_expect = ['buildLabel', 'buildDetails'];
    $br = new BuildResults('foo', 'bar');
    // Build::saveBuildState() uses json_encode() to generate an object.
    // If we change that to generate an array, we should change it here.
    $json = json_encode($br);
    $decode = json_decode($json);
    foreach ($keys_we_expect as $key) {
      $this->assertObjectHasAttribute($key, $decode);
    }
  }

}
