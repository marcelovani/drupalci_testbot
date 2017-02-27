<?php

namespace DrupalCI\Build\Artifact\Junit;

use DrupalCI\Injectable;
use Pimple\Container;

class JunitXmlBuilder implements Injectable {

  /**
   * The results database to query.
   *
   * @var  \DrupalCI\Build\Environment\DatabaseInterface
   */
  protected $resultsDatabase;

  /**
   * The container environment
   *
   * @var  \DrupalCI\Build\Environment\EnvironmentInterface
   */
  protected $environment;

  /**
   * {@inheritdoc}
   */
  public function inject(Container $container) {
    $this->resultsDatabase = $container['db.results'];
    $this->environment = $container['environment'];
  }

  /**
   * Generate a Junit XML DOM object based on the test list and results db.
   *
   * @param string[] $test_groups
   *   Array of test groups, keyed by class name.
   */
  public function generate(array $test_groups) {
    $db = $this->resultsDatabase->connect($this->resultsDatabase->getDbname());

    $q_result = $db->query('SELECT * FROM simpletest ORDER BY test_id, test_class, message_id;');

    $mapped_results = [];

    while ($result = $q_result->fetch(\PDO::FETCH_ASSOC)) {
      $mapped_results = array_merge_recursive(
        $mapped_results,
        $this->mapDbResult($result, $test_groups)
      );
    }

    return $this->buildXml($mapped_results);
  }

  /**
   * Map a single {simpletest} result array to Junit array structure.
   *
   * @param array $result
   *   Result row from a query to {simpletest}
   * @param string[] $test_groups
   *   Array of groups, keyed by class name.
   *
   * @return array
   *   Simpletest result data mapped to Junit-consumable array structure.
   */
  protected function mapDbResult($result, $test_groups) {
    $mapped_result = [];
    // query for simpletest results
    $results_map = array(
      'pass' => 'Pass',
      'fail' => 'Fail',
      'exception' => 'Exception',
      'debug' => 'Debug',
    );
    if (isset($results_map[$result['status']])) {
      $test_class = $result['test_class'];
      // Set the group from the lookup table
      $test_group = $test_groups[$test_class];

      // Cleanup the class, and the parens from the test method name
      $test_method = preg_replace('/.*>/', '', $result['function']);
      $test_method = preg_replace('/\(\)/', '', $test_method);

      // Trim source directory path off of $result['file'].
      $length = strlen($this->environment->getExecContainerSourceDir());
      $result['file'] = substr($result['file'], $length + 1);

      // Set up our return array.
      $mapped_result[$test_group][$test_class][$test_method][] = array(
        'status' => $result['status'],
        'type' => $result['message_group'],
        'message' => strip_tags(htmlspecialchars_decode($result['message'], ENT_QUOTES)),
        'line' => $result['line'],
        'file' => $result['file'],
      );
    }
    return $mapped_result;
  }

  /**
   *
   * @param type $test_result_data
   *   Result data formatted for XML. This is typically the output of
   *   mapDbResult().
   *
   * @return \DOMDocument
   *   JUnit XML dom document.
   */
  protected function buildXml($test_result_data) {
    // Maps statuses to their xml element for each testcase.
    $element_map = array(
      'pass' => 'system-out',
      'fail' => 'failure',
      'exception' => 'error',
      'debug' => 'system-err',
    );
    // Create an xml file per group?

    $test_group_id = 0;
    $doc = new \DOMDocument('1.0');
    $test_suites = $doc->createElement('testsuites');

    // TODO: get test name data from the build.
    $test_suites->setAttribute('name', "TODO SET");
    $test_suites->setAttribute('time', "TODO SET");
    $total_failures = 0;
    $total_tests = 0;
    $total_exceptions = 0;

    // Go through the groups, and create a testsuite for each.
    foreach ($test_result_data as $groupname => $group_classes) {
      $group_failures = 0;
      $group_tests = 0;
      $group_exceptions = 0;
      $test_suite = $doc->createElement('testsuite');
      $test_suite->setAttribute('id', $test_group_id);
      $test_suite->setAttribute('name', $groupname);
      // While more pure, we should probably inject a date/time service.
      // For now we do not need the timestamp on group data.
      //  $test_suite->setAttribute('timestamp', date('c'));
      $test_suite->setAttribute('hostname', "TODO: Set Hostname");
      $test_suite->setAttribute('package', $groupname);
      // TODO: time test runs. $test_group->setAttribute('time', $test_group_id);
      // TODO: add in the properties of the build into the test run.

      // Loop through the classes in each group
      foreach ($group_classes as $class_name => $class_methods) {
        foreach ($class_methods as $test_method => $method_results) {
          $test_case = $doc->createElement('testcase');
          $test_case->setAttribute('classname', $groupname . "." . $class_name);
          $test_case->setAttribute('name', $test_method);
          $test_case_status = 'pass';
          $test_case_assertions = 0;
          $test_case_exceptions = 0;
          $test_case_failures = 0;
          $test_output = '';
          $fail_output = '';
          $exception_output = '';
          foreach ($method_results as $assertion) {
            $assertion_result = $assertion['status'] . ": [" . $assertion['type'] . "] Line " . $assertion['line'] . " of " . $assertion['file'] . ":\n" . $assertion['message'] . "\n\n";
            $assertion_result = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '�', $assertion_result);

            // Keep track of overall assersions counts
            if (!isset($assertion_counter[$assertion['status']])) {
              $assertion_counter[$assertion['status']] = 0;
            }
            $assertion_counter[$assertion['status']]++;
            if ($assertion['status'] == 'exception') {
              $test_case_exceptions++;
              $group_exceptions++;
              $total_exceptions++;
              $test_case_status = 'failed';
              $exception_output .= $assertion_result;
            }
            else if ($assertion['status'] == 'fail') {
              $test_case_failures++;
              $group_failures++;
              $total_failures++;
              $test_case_status = 'failed';
              $fail_output .= $assertion_result;
            }
            elseif (($assertion['status'] == 'debug')) {
              $test_output .= $assertion_result;
            }

            $test_case_assertions++;
            $group_tests++;
            $total_tests++;

          }
          if ($test_case_failures > 0) {
            $element = $doc->createElement("failure");
            $element->setAttribute('message', $fail_output);
            $element->setAttribute('type', "fail");
            $test_case->appendChild($element);
          }

          if ($test_case_exceptions > 0 ) {
            $element = $doc->createElement("error");
            $element->setAttribute('message', $exception_output);
            $element->setAttribute('type', "exception");
            $test_case->appendChild($element);
          }
          $std_out = $doc->createElement('system-out');
          $output = $doc->createCDATASection($test_output);
          $std_out->appendChild($output);
          $test_case->appendChild($std_out);

          // TODO: Errors and Failures need to be set per test Case.
          $test_case->setAttribute('status', $test_case_status);
          $test_case->setAttribute('assertions', $test_case_assertions);
          // $test_case->setAttribute('time', "TODO: track time");

          $test_suite->appendChild($test_case);

        }
      }

      // Should this count the tests as part of the loop, or just array_count?
      $test_suite->setAttribute('tests', $group_tests);
      $test_suite->setAttribute('failures', $group_failures);
      $test_suite->setAttribute('errors', $group_exceptions);
      /* TODO: Someday simpletest will disable or skip tests based on environment
      $test_group->setAttribute('disabled', $test_group_id);
      $test_group->setAttribute('skipped', $test_group_id);
       */
      $test_suites->appendChild($test_suite);
      $test_group_id++;
    }
    $test_suites->setAttribute('tests', $total_tests);
    $test_suites->setAttribute('failures', $total_failures);
    // $test_suites->setAttribute('disabled', "TODO SET");
    $test_suites->setAttribute('errors', $total_exceptions);
    $doc->appendChild($test_suites);
    $label = '';
    if (isset($this->pluginLabel)) {
      $label = $this->pluginLabel . ".";
    }
    return $doc;
  }

}
