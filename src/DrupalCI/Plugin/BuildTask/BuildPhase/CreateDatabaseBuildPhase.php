<?php

namespace DrupalCI\Plugin\BuildTask\BuildPhase;

use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTask\BuildPhase\BuildPhaseInterface;
use DrupalCI\Plugin\BuildTaskBase;

/**
 * @PluginID("create_db")
 */
class CreateDatabaseBuildPhase extends BuildTaskBase implements BuildPhaseInterface, BuildTaskInterface {

}
