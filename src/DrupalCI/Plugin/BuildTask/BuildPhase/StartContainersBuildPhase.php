<?php

namespace DrupalCI\Plugin\BuildTask\BuildPhase;

use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildPhase\BuildPhaseInterface;
use DrupalCI\Plugin\BuildTaskBase;

/**
 * @PluginID("startcontainers")
 */

class StartContainersBuildPhase extends BuildTaskBase implements BuildPhaseInterface, BuildTaskInterface  {

}
