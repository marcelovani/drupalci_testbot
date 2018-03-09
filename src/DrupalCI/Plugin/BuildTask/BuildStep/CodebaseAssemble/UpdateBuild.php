<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Plugin\BuildTaskBase;
use Pimple\Container;
use DrupalCI\Plugin\MessageBase;


/**
 * Reload the assessment phase if drupalci.yml has changed.
 *
 * @PluginID("update_build")
 */
class UpdateBuild extends BuildTaskBase {

  /**
   * @var \DrupalCI\Build\Codebase\CodebaseInterface
   */
  protected $codebase;

  /**
   * YAML parser service
   *
   * @var \Symfony\Component\Yaml\Yaml
   */
  protected $yaml;

  public function inject(Container $container) {
    parent::inject($container);
    $this->codebase = $container['codebase'];
    $this->yaml = $container['yaml.parser'];
  }

  /**
   * @inheritDoc
   */
  public function getDefaultConfiguration() {
    // The only configuration is whether we should halt the build if there's any
    // problem.
    return [
      'die-on-fail' => TRUE,
    ];
  }

  protected function locateDrupalCiYmlFile() {
    return __DIR__ . '/drupalci.yml';
    // @todo: Adjust to make sure this works for contrib.
//    return $this->codebase->getSourceDirectory() . '/drupalci.yml';
  }

  protected function shouldReplaceAssessmentStage() {
    // Is drupalci.yml modified?
    return in_array(
      $this->locateDrupalCiYmlFile(),
      $this->codebase->getModifiedFiles()
    );
  }

  /**
   * {@inheritDoc}
   */
  public function run() {
    // The rules:
    // - Is drupalci.yml modified? No? We're done.
    // - If yes, replace the build's assessment phase with the contents of
    //   drupalci.yml for the current event.

    if (!$this->shouldReplaceAssessmentStage()) {
      $this->io->writeln('This build does not contain a modified drupalci.yml file.');
 //     return 0;
    }

    $drupalci_yml_file = $this->locateDrupalCiYmlFile();
    if (!file_exists($drupalci_yml_file)) {
      $this->io->writeln('drupalci.yml file does not exist');
      return 0;
    }
    else {
      $this->io->writeln('Using: ' . $drupalci_yml_file);
    }

    // @todo: Figure out the build event.
    $build_event = 'build';

    $drupalci_yml = $this->yaml->parse(file_get_contents($drupalci_yml_file));

    if (isset($drupalci_yml[$build_event]['assessment'])) {
      $this->io->writeln('Replacing assessment stage with drupalci.yml');
      $project_build = $drupalci_yml[$build_event]['assessment'];
    }

    $this->build->setAssessmentBuildDefinition($project_build);

    return 0;
  }

}
