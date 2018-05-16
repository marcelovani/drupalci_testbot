<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Plugin\BuildTask\BuildStep\BuildStepInterface;
use DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble\HostComposer;
use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskBase;
use Pimple\Container;

/**
 * BC layer that no-ops the container_composer command that only really existed
 * as a doublecheck that we dont need anymore.
 *
 * TODO: remove at least 180 days past 5-17-2018
 *
 * @PluginID("container_composer")
 */
class ContainerComposerBC extends BuildTaskBase implements BuildStepInterface, BuildTaskInterface {

}
