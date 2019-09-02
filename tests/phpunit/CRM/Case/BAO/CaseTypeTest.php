<?php

/**
 * Class CRM_Case_BAO_CaseTypeTest
 * @group headless
 */
class CRM_Case_BAO_CaseTypeTest extends CiviUnitTestCase {

  /**
   * Provide a series of test-scenarios. Each scenario includes a case-type definition expressed as
   * JSON and equivalent XML.
   *
   * @return array
   */
  public function definitionProvider() {
    $fixtures['empty-defn'] = [
      'json' => json_encode([]),
      'xml' => file_get_contents(__DIR__ . '/xml/empty-defn.xml'),
    ];

    $fixtures['empty-lists'] = [
      'json' => json_encode([
        'activitySets' => [],
        'activityTypes' => [],
        'caseRoles' => [],
        'timelineActivityTypes' => [],
      ]),
      'xml' => file_get_contents(__DIR__ . '/xml/empty-lists.xml'),
    ];

    $fixtures['one-item-in-each'] = [
      'json' => json_encode([
        'activityTypes' => [
          ['name' => 'First act (foréign éxamplé, &c)'],
        ],
        'activitySets' => [
          [
            'name' => 'set1',
            'label' => 'Label 1',
            'timeline' => 1,
            'activityTypes' => [
              ['name' => 'Open Case', 'status' => 'Completed'],
            ],
          ],
        ],
        'timelineActivityTypes' => [
          ['name' => 'Open Case', 'status' => 'Completed'],
        ],
        'caseRoles' => [
          ['name' => 'First role', 'machineName' => 'First role', 'creator' => 1, 'manager' => 1],
        ],
      ]),
      'xml' => file_get_contents(__DIR__ . '/xml/one-item-in-each.xml'),
    ];

    $fixtures['two-items-in-each'] = [
      'json' => json_encode([
        'activityTypes' => [
          ['name' => 'First act'],
          ['name' => 'Second act'],
        ],
        'activitySets' => [
          [
            'name' => 'set1',
            'label' => 'Label 1',
            'timeline' => 1,
            'activityTypes' => [
              ['name' => 'Open Case', 'status' => 'Completed'],
              [
                'name' => 'Meeting',
                'reference_activity' => 'Open Case',
                'reference_offset' => 1,
                'reference_select' => 'newest',
              ],
            ],
          ],
          [
            'name' => 'set2',
            'label' => 'Label 2',
            'sequence' => 1,
            'activityTypes' => [
              ['name' => 'First act'],
              ['name' => 'Second act'],
            ],
          ],
        ],
        'timelineActivityTypes' => [
          ['name' => 'Open Case', 'status' => 'Completed'],
          [
            'name' => 'Meeting',
            'reference_activity' => 'Open Case',
            'reference_offset' => 1,
            'reference_select' => 'newest',
          ],
        ],
        'caseRoles' => [
          ['name' => 'First role', 'machineName' => 'First role', 'creator' => 1, 'manager' => 1],
          ['name' => 'Second role', 'machineName' => 'Second role'],
        ],
      ]),
      'xml' => file_get_contents(__DIR__ . '/xml/two-items-in-each.xml'),
    ];

    $fixtures['forkable-0'] = [
      'json' => json_encode([
        'forkable' => 0,
      ]),
      'xml' => file_get_contents(__DIR__ . '/xml/forkable-0.xml'),
    ];

    $fixtures['forkable-1'] = [
      'json' => json_encode([
        'forkable' => 1,
      ]),
      'xml' => file_get_contents(__DIR__ . '/xml/forkable-1.xml'),
    ];

    $cases = [];
    foreach ([
      'empty-defn',
      'empty-lists',
      'one-item-in-each',
      'two-items-in-each',
      'forkable-0',
      'forkable-1',
    ] as $key) {
      $cases[] = [$key, $fixtures[$key]['json'], $fixtures[$key]['xml']];
    }
    return $cases;
  }

  /**
   * @param string $fixtureName
   * @param string $expectedJson
   * @param string $inputXml
   * @dataProvider definitionProvider
   */
  public function testConvertXmlToDefinition($fixtureName, $expectedJson, $inputXml) {
    $xml = simplexml_load_string($inputXml);
    $expectedDefinition = json_decode($expectedJson, TRUE);
    $actualDefinition = CRM_Case_BAO_CaseType::convertXmlToDefinition($xml);
    $this->assertEquals($expectedDefinition, $actualDefinition);
  }

  /**
   * @param string $fixtureName
   * @param string $inputJson
   * @param string $expectedXml
   * @dataProvider definitionProvider
   */
  public function testConvertDefinitionToXml($fixtureName, $inputJson, $expectedXml) {
    $inputDefinition = json_decode($inputJson, TRUE);
    $actualXml = CRM_Case_BAO_CaseType::convertDefinitionToXML('Housing Support', $inputDefinition);
    $this->assertEquals($this->normalizeXml($expectedXml), $this->normalizeXml($actualXml));
  }

  /**
   * @param string $fixtureName
   * @param string $ignore
   * @param string $inputXml
   * @dataProvider definitionProvider
   */
  public function testRoundtrip_XmlToJsonToXml($fixtureName, $ignore, $inputXml) {
    $tempDefinition = CRM_Case_BAO_CaseType::convertXmlToDefinition(simplexml_load_string($inputXml));
    $actualXml = CRM_Case_BAO_CaseType::convertDefinitionToXML('Housing Support', $tempDefinition);
    $this->assertEquals($this->normalizeXml($inputXml), $this->normalizeXml($actualXml));
  }

  /**
   * @param string $fixtureName
   * @param string $inputJson
   * @param string $ignore
   * @dataProvider definitionProvider
   */
  public function testRoundtrip_JsonToXmlToJson($fixtureName, $inputJson, $ignore) {
    $tempXml = CRM_Case_BAO_CaseType::convertDefinitionToXML('Housing Support', json_decode($inputJson, TRUE));
    $actualDefinition = CRM_Case_BAO_CaseType::convertXmlToDefinition(simplexml_load_string($tempXml));
    $expectedDefinition = json_decode($inputJson, TRUE);
    $this->assertEquals($expectedDefinition, $actualDefinition);
  }

  /**
   * Normalize the whitespace in an XML document.
   *
   * @param string $xml
   * @return string
   */
  public function normalizeXml($xml) {
    return trim(
      // tags on new lines
      preg_replace(":\n*<:", "\n<",
        // no leading whitespace
        preg_replace("/\n[\n ]+/", "\n",
          $xml
        )
      )
    );
  }

}
