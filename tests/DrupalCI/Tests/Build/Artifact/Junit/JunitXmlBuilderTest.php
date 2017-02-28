<?php

namespace DrupalCI\Tests\Build\Artifact\Junit;

use DrupalCI\Tests\DrupalCITestCase;
use DrupalCI\Build\Artifact\Junit\JunitXmlBuilder;

/**
 * @coversDefaultClass DrupalCI\Build\Artifact\Junit\JunitXmlBuilder
 */
class JunitXmlBuilderTest extends DrupalCITestCase {

  /**
   * @covers ::mapDbResult
   */
  public function provideMapDbResult() {
    return [
      'no_status' => [[], ['status' => 'unmappable_status'], []],
      'passing_test' => [
        [
          // Expected result.
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
        // Mocked {simpletest} row.
        [
          'status' => 'pass',
          'test_class' => 'Test\Class',
          'function' => 'testFunction',
          'file' => 'long/enough/to/be/snipped',
          'message_group' => 'Other',
          'message' => 'This test is awesome.',
          'line' => '23',
        ],
        // Test groups from testgroups.txt.
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

  public function provideBuildXml() {
    return [
      'empty' => [
        "<?xml version=\"1.0\"?>\n<testsuites name=\"TODO SET\" time=\"TODO SET\" tests=\"0\" failures=\"0\" errors=\"0\"/>\n",
        [],
      ],
      'passing_test' => [
        "<?xml version=\"1.0\"?>\n<testsuites name=\"TODO SET\" time=\"TODO SET\" tests=\"1\" failures=\"0\" errors=\"0\"><testsuite id=\"0\" name=\"result_test_group\" hostname=\"TODO: Set Hostname\" package=\"result_test_group\" tests=\"1\" failures=\"0\" errors=\"0\"><testcase classname=\"result_test_group.Test\Class\" name=\"testFunction\" status=\"pass\" assertions=\"1\"><system-out><![CDATA[]]></system-out></testcase></testsuite></testsuites>\n",
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
                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::buildXml
   * @dataProvider provideBuildXml
   */
  public function testBuildXml($expected, $test_result_data) {
    $xml_builder = new JunitXmlBuilder();
    $xml_builder->inject($this->getContainer());

    $ref_build_xml = new \ReflectionMethod($xml_builder, 'buildXml');
    $ref_build_xml->setAccessible(TRUE);

    $doc = $ref_build_xml->invokeArgs($xml_builder, [$test_result_data]);

    $this->assertXmlStringEqualsXmlString($expected, $doc->saveXML());
  }

}
