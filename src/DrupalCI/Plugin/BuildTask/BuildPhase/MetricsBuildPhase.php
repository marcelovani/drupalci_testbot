<?php

namespace DrupalCI\Plugin\BuildTask\BuildPhase;

use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildPhase\BuildPhaseInterface;
use DrupalCI\Plugin\BuildTaskBase;

/**
 * @PluginID("metrics")
 */
class MetricsBuildPhase extends BuildTaskBase implements BuildPhaseInterface, BuildTaskInterface  {

}
