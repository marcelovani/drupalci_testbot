<?php

namespace DrupalCI\Plugin\BuildTask\BuildStep\CodebaseAssemble;

use DrupalCI\Plugin\BuildTaskBase;
use Pimple\Container;
use DrupalCI\Build\Codebase\CodebaseInterface;
use DrupalCI\Plugin\MessageBase;


/**
 * Reload the assessment phase if drupalci.yml has changed.
 *
 * @PluginID("reload_assessment")
 */
class ReloadAssessment extends BuildTaskBase {

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

  public function addMessageToAssessment($message = 'woot') {
    $ref_assessment = new \ReflectionProperty($this->build, 'assessmentComputedBuildDefinition');
    $ref_assessment->setAccessible(TRUE);

    $build = $ref_assessment->getValue($this->build);

    $validate_codebase = $build['assessment']['validate_codebase'];
    $validate_codebase = array_merge(
      ['message' => ['message' => 'kilroy was here', 'style' => 'error']],
      $validate_codebase
    );

    $build['assessment']['validate_codebase'] = $validate_codebase;

    $this->build->setAssessmentBuildDefinition($build['assessment']);
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

    $message = 'HELLOOOOOOO.....';

    // @todo: Figure out the build event.
    $build_event = 'build';

    $drupalci_yml = $this->yaml->parse(file_get_contents($drupalci_yml_file));

    if (isset($drupalci_yml[$build_event]['assessment'])) {
      $message = 'This assessment stage was replaced with drupalci.yml.';
      $project_build = $drupalci_yml[$build_event]['assessment'];
    }

    if (empty($project_build['validate_codebase'])) {
      $project_build['validate_codebase'] = [];
    }
    $project_build['validate_codebase'] = array_merge(
      MessageBase::generateDefinition($message),
      $project_build['validate_codebase']
    );

    $this->build->setAssessmentBuildDefinition($project_build);

    return 0;


    // Load drupalci.yml.

/*
    if ('TRUE' === strtoupper(getenv('DCI_Debug'))) {
      $verbose = ' --verbose';
      $progress = '';
    } else {
      $verbose = '';
      $progress = ' --no-progress';
    }
    $output = [];
    $result = 0;
    $this->io->writeln('Executing yarn install for core nodejs dev dependencies.');

    $work_dir = $this->codebase->getSourceDirectory() . '/core';
    $this->exec("yarn${verbose} install${progress} --non-interactive --cwd ${work_dir} 2>&1", $output, $result);

    $this->saveStringArtifact('yarn_install.txt', implode("\n", $output));

    if ($result !== 0) {
      $message = "Yarn install command returned code: $result";
      if ($this->configuration['die-on-fail']) {

        $this->terminateBuild($message, implode("\n", $output));
      }
      else {
        $this->io->writeln($message . "\nYarn install failed; Proceeding anyways...");
        return 0;
        // Skip the list and licenses below.
      }
    } else {
      $this->io->writeln('Yarn install success');
    }
    $output = [];
    $this->exec("yarn${verbose} list$progress --non-interactive --cwd ${work_dir} 2>&1", $output, $result);
    $this->saveStringArtifact('yarn_list.txt', implode("\n", $output));
    $output = [];
    $this->exec("yarn${verbose}$progress --non-interactive --cwd ${work_dir} licenses list 2>&1", $output, $result);
    $this->saveStringArtifact('yarn_licenses.txt', implode("\n", $output));
    return $result;*/
  }

}
