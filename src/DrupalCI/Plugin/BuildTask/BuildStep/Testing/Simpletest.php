<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\Testing;

use DrupalCI\Build\Artifact\Junit\JunitXmlBuilder;
use DrupalCI\Build\BuildInterface;
use DrupalCI\Build\Environment\Environment;
use DrupalCI\Injectable;
use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use Pimple\Container;

/**
 * @PluginID("simpletest")
 */
class Simpletest extends RunTests implements BuildStepInterface {

}
