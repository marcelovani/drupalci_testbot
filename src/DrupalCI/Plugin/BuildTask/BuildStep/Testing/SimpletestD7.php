<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\Testing;

use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Environment\Environment;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use Pimple\Container;

/**
 * Alias plugin for the run_tests_d7 plugin
 *
 * @PluginID("simpletest_d7")
 */
class SimpletestD7 extends RunTestsD7 implements BuildStepInterface {

}
