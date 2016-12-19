<?php

namespace DrupalCI\Plugin\BuildTask\BuildPhase;

use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildPhase\BuildPhaseInterface;
use DrupalCI\Plugin\BuildTaskBase;

/**
 * @PluginID("assemble_codebase")
 */
class CodebaseAssembleBuildPhase extends BuildTaskBase implements BuildPhaseInterface, BuildTaskInterface  {

}
