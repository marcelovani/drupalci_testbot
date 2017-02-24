<?php

namespace DrupalCI\Plugin\BuildTask\BuildStage;

use DrupalCI\Plugin\BuildTask\BuildTaskInterface;
use DrupalCI\Plugin\BuildTaskBase;
use Pimple\Container;

/**
 * @PluginID("codebase")
 */

class CodebaseBuildStage extends BuildTaskBase implements BuildStageInterface, BuildTaskInterface {

  /* @var \DrupalCI\Build\Codebase\CodebaseInterface */
  protected $codebase;

  public function inject(Container $container) {
    parent::inject($container);
    $this->codebase = $container['codebase'];
  }

  /**
   * @inheritDoc
   */
  public function configure() {
    // DCI_TestItem may have --directory modules/<projectname>, if it does,
    // we can assume that it is the module we wish to test, and therefore needs
    // to be built in the codebase tmp directory and pointed to by composer.
    if (FALSE !== getenv(('DCI_TestItem'))) {
      $this->configuration['project_subdir'] = $this->getProjectSubDir(getenv(('DCI_TestItem')));
      // @TODO: change this to DCI_Projectname once https://www.drupal.org/node/2853889 is solved.
      $this->configuration['project_name'] = $this->getProjectName(getenv(('DCI_TestItem')));
    }
    if (FALSE !== getenv(('DCI_ProjectType'))) {
      $this->configuration['project_type'] = getenv('DCI_ProjectType');
    }
    if (FALSE !== getenv(('DCI_ProjectName'))) {
      $this->configuration['project_name'] = getenv('DCI_ProjectName');
    }

  }

  /**
   * @inheritDoc
   */
  public function run() {

    if (!empty($this->configuration['project_subdir'])) {
      $this->codebase->setExtensionProjectSubdir($this->configuration['project_subdir']);
    }
    if (!empty($this->configuration['project_name']) && $this->configuration['project_name'] !== 'drupal') {
      $this->codebase->setProjectName($this->configuration['project_name']);
      // @TODO: this assumes that the project type is module
      // once https://www.drupal.org/node/2853889 is in, we can rely on that for
      // the project type.

      $this->codebase->setProjectType('module');
    }
    if (!empty($this->configuration['project_type'])) {
      $this->codebase->setProjectType($this->configuration['project_type']);
    }
    $this->codebase->setupDirectories();

  }

  /**
   * @inheritDoc
   */
  public function complete($childStatus) {
    $this->saveHostArtifact($this->codebase->getSourceDirectory() . '/vendor/composer/installed.json', 'composer-installed.json');

    $extensionDir = '';
    if (!empty($this->codebase->getTrueExtensionSubDirectory())){
      $extensionDir = '/' . $this->codebase->getTrueExtensionSubDirectory();
    }
    $project_build_dir = ['projectDirectory' => $this->codebase->getSourceDirectory() . $extensionDir];
    $this->saveStringArtifact('project_directory.json',json_encode($project_build_dir));
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'project_subdir' => '',
      'project_name' => '',
      'project_type' => '',
    ];

  }

  protected function getProjectSubDir($testitem) {
    if (strpos($testitem, 'directory') === 0) {
      $components = explode(':', $testitem);
      return $components[1];
    }
    return FALSE;
  }

  protected function getProjectName($testitem) {
    if (strpos($testitem, 'directory') === 0) {
      $components = explode(':', $testitem);
      $pathcomponents = explode("/", $components[1]);
    }
    if (!empty($pathcomponents)) {
      return array_pop($pathcomponents);
    } else {
      return 'drupal';
    }
  }

}
