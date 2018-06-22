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
    if (FALSE !== getenv(('DCI_ProjectType'))) {
      $this->configuration['project-type'] = getenv('DCI_ProjectType');
    }
    if (FALSE !== getenv(('DCI_ProjectName'))) {
      $this->configuration['project-name'] = getenv('DCI_ProjectName');
    }

  }

  /**
   * @inheritDoc
   */
  public function run() {

    // TODO: ignore project_subdir
    if (!empty($this->configuration['project-subdir'])) {
      $this->codebase->setExtensionProjectSubdir($this->configuration['project-subdir']);
    }
    if (!empty($this->configuration['project-name']) && $this->configuration['project-name'] !== 'drupal') {
      $this->codebase->setProjectName($this->configuration['project-name']);
      // @TODO: this assumes that the project type is module
      // once https://www.drupal.org/node/2853889 is in, we can rely on that for
      // the project type.


    }
    if (!empty($this->configuration['project-type'])) {
      $this->codebase->setProjectType($this->configuration['project-type']);
    }
    else if (!empty($this->configuration['project-subdir'])) {
      $this->configuration['project-subdir'] = str_replace('sites/all/','',$this->configuration['project-subdir']);
      $pathcomponents = explode('/', $this->configuration['project-subdir']);

      $project_type = rtrim($pathcomponents[0], 's');
      $this->codebase->setProjectType($project_type);
    }

  }

  /**
   * @inheritDoc
   */
  public function complete($childStatus) {
    // The build definition can be changed during the codebase stage, because
    // drupalci.yml can be patched. Therefore we have to store the new build as
    // a final stage here.
    $this->build->saveModifiedBuildDefiniton();

    $this->saveHostArtifact($this->codebase->getSourceDirectory() . '/vendor/composer/installed.json', 'composer-installed.json');

    $project_build_dir = ['projectDirectory' => $this->codebase->getProjectSourceDirectory()];
    $this->saveStringArtifact(json_encode($project_build_dir), 'project_directory.json');
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    return [
      'project-subdir' => '',
      'project-name' => '',
      'project-type' => '',
    ];

  }

}
