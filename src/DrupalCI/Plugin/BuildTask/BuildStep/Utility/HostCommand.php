<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\Utility;

use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskBase;
use DrupalCI\Plugin\BuildTask\BuildTaskException;
use Pimple\Container;

/**
 * @PluginID("host_command")
 */
class HostCommand extends Command implements BuildStepInterface, BuildTaskInterface {

}
