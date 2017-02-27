<?php

namespace DrupalCI\Tests\Build\Artifact\Junit;

use DrupalCI\Tests\DrupalCITestCase;
use DrupalCI\Build\Artifact\Junit\JunitXmlBuilder;

class JunitXmlBuilderTest extends DrupalCITestCase {

  public function provideMapDbResult() {
    return [
      'no_status' => [[], ['status' => 'unmappable_status'], []],
      'passing_test' => [
        [
          'result_test_group' => [
            'Test\Class' => [
              'testFunction' => [
                [
                  'status' => 'pass',
                  'type' => 'Other',
                  'message' => 'This test is awesome.',
                  'line' => '23',
                  'file' => '/be/snipped',
                ]
              ],
            ],
          ],
        ],
        [
          'status' => 'pass',
          'test_class' => 'Test\Class',
          'function' => 'testFunction',
          'file' => 'long/enough/to/be/snipped',
          'message_group' => 'Other',
          'message' => 'This test is awesome.',
          'line' => '23',
        ],
        [
          'Test\Class' => 'result_test_group',
        ]
      ],

    ];
  }

  /**
   * @dataProvider provideMapDbResult
   */
  public function testMapDbResult($expected, $result, $test_groups) {
    $xml_builder = new JunitXmlBuilder();
    $xml_builder->inject($this->getContainer());

    $ref_map_db_result = new \ReflectionMethod($xml_builder, 'mapDbResult');
    $ref_map_db_result->setAccessible(TRUE);

    $mapped = $ref_map_db_result->invokeArgs($xml_builder, [$result, $test_groups]);

    $this->assertEquals($expected, $mapped);
  }

}
