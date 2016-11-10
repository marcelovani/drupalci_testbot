<?php

/**
 * @file
 * Build class for SimpleTest builds on DrupalCI.
 */

namespace DrupalCI\Plugin\BuildTypes\simpletest;

use DrupalCI\Build\Build;

/**
 * @PluginID("simpletest")
 */

class SimpletestBuild extends Build {

  public $availableArguments = array(
    // ***** Variables Available for any build type *****
    'DCI_UseLocalCodebase' => 'Used to define a local codebase to be cloned (instead of performing a Git checkout)',
    'DCI_CheckoutDir' => 'Defines the location to be used in creating the local copy of the codebase, to be mapped into the container as a container volume.',
    'DCI_ResultsServer' => 'Specifies the url string of a DrupalCI results server for which to publish build results',
    'DCI_ResultsServerConfig' => 'Specifies the location of a configuration file on the test runner containg a DrupalCI Results Server configuration to use in publishing results.',
    'DCI_BuildId' => 'Specifies a unique build ID assigned to this build from an upstream server',
    'DCI_JobType' => 'Specifies a default build type to assume for a "drupalci run" command',
    'DCI_EXCLUDE' => 'Specifies whether to exclude the .git directory during a clone of a local codebase.',

    // ***** Default Variables defined for every simpletest build *****
    'DCI_DBVersion' => 'Defines the database version for this particular simpletest run. May map to a required service container.',
    'DCI_PHPVersion' => 'Defines the PHP Version used within the executable container for this build type.',
    'DCI_CoreRepository' => 'Defines the primary repository to be checked out while building the codebase to test.',
    'DCI_CoreBranch' => 'Defines the branch on the primary repository to be checked out while building the codebase to test.',
    'DCI_GitCheckoutDepth' => 'Defines the depth parameter passed to git clone while checking out the core repository.',
    'DCI_RunScript' => 'Defines the default run script to be executed on the container.',
    'DCI_RunOptions' => 'A string containing a series of any other run script options to append to the run script when performing a build.',
    'DCI_DBUser' => 'Defines the default database user to be used on the site database.',
    'DCI_DBPassword' => 'Defines the default database password to be used on the site database.',
    'DCI_DBUrl' => 'Define the --dburl parameter to be passed to the run script.',
    'DCI_TestGroups' => 'Defines the target test groups to run.',
    'DCI_Concurrency' => 'Defines the value to pass to the --concurrency argument of the run script.',
    'DCI_XMLOutput' => 'Defines the directory where xml results will be stored.',
    'DCI_PHPInterpreter' => 'Defines the php interpreter to be passed to the Run Script in the --php argument.',


    // ***** Optional arguments *****
    'DCI_DieOnFail' => 'Defines whether to include the --die-on-fail flag in the Run Script options',
    'DCI_Fetch' => 'Used to specify any files which should be downloaded while building out the codebase.',
    // Syntax: 'url1,relativelocaldirectory1;url2,relativelocaldirectory2;...'
    'DCI_Patch' => 'Defines any patches which should be applied while building out the codebase.',
    // Syntax: 'localfile1,applydirectory1;localfile2,applydirectory2;...'
    'DCI_ResultsDirectory' => 'Defines the local directory within the container where the xml results file should be written.',
    'DCI_RunScriptArguments' => 'An array of other build script options which will be added to the runScript command when executing a build.',
    // Syntax: 'argkey1,argvalue1;argkey2,argvalue2;argkey3;argkey4,argvalue4;'
    'DCI_GitCommitHash' => 'This allows to checkout specific core commits, useful for regression testing',
  );

}
