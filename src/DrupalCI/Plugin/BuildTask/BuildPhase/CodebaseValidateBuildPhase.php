<?php

namespace DrupalCI\Plugin\BuildTask\BuildPhase;

use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildPhase\BuildPhaseInterface;
use DrupalCI\Plugin\BuildTaskBase;

/**
 * @PluginID("validate_codebase")
 */
class CodebaseValidateBuildPhase extends BuildTaskBase implements BuildPhaseInterface, BuildTaskInterface  {

}
