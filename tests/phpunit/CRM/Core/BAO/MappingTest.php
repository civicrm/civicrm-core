<?php

/**
 * Class CRM_Core_BAO_MappingTest.
 *
 * @group headless
 */
class CRM_Core_BAO_MappingTest extends CiviUnitTestCase {

  /**
   * Cleanup after test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $this->quickCleanup(['civicrm_mapping_field', 'civicrm_mapping']);
    parent::tearDown();
  }

  /**
   * Test calling saveMapping.
   *
   * @dataProvider getMappingMaps
   */
  public function testSaveMappingFields($params, $expected) {
    // The data provider runs before the relationship types exist, so it gives names; look up the ids.
    foreach ($params['mapper'] as &$mapperFields) {
      foreach ($mapperFields as &$mapperField) {
        if (preg_match('/^(.+)_(a_b|b_a)$/', $mapperField[1] ?? '', $matches)) {
          $mapperField[1] = $this->getRelationshipTypeID($matches[1]) . '_' . $matches[2];
        }
      }
      unset($mapperField);
    }
    unset($mapperFields);
    foreach ($expected as &$expectedField) {
      if (isset($expectedField['relationship_type_id'])) {
        $expectedField['relationship_type_id'] = (string) $this->getRelationshipTypeID($expectedField['relationship_type_id']);
      }
    }
    unset($expectedField);
    $mapping = $this->callAPISuccess('Mapping', 'create', ['name' => 'teest']);
    CRM_Core_BAO_Mapping::saveMappingFields($params, $mapping['id']);
    $saved = $this->callAPISuccess('MappingField', 'get', ['mapping_id' => $mapping['id'], 'sequential' => 1])['values'];
    $expected = array_values($expected);
    foreach ($expected as $index => $mappingField) {
      $expected[$index]['id'] = $saved[$index]['id'];
      $expected[$index]['mapping_id'] = $mapping['id'];
    }
    $this->assertEquals($expected, $saved);
  }

  /**
   * Get data for mapping test.
   *
   * @return array
   */
  public static function getMappingMaps() {
    return [
      [
        'params' => [
          'qfKey' => '8d3bae0f77b62314516c1253176a1c1a_6756',
          'entryURL' => 'http://dmaster.local/civicrm/contribute/search?reset=1',
          'saveMappingName' => 'test',
          'saveMappingDesc' => '',
          'saveMapping' => '1',
          'mapper' => [
            1 =>
              [
                ['Individual', 'Supervised by_b_a', 'id'],
                ['Individual', 'city', ' '],
                ['Individual', 'contact_sub_type'],
                ['Student', 'custom_27'],
                ['Individual', 'current_employer'],
                ['Individual', 'phone', '1', '2'],
                ['Individual', 'postal_code', '2'],
                ['Individual', 'im', '1', '1'],
                ['Individual', 'url'],
                ['Individual', 'Child of_b_a', 'phone', '5', '1'],
              ],
          ],
        ],
        'expected' => $expected = [
          1 =>
            [
              'name' => 'id',
              'contact_type' => 'Individual',
              'column_number' => '0',
              'relationship_type_id' => 'Supervised by',
              'relationship_direction' => 'b_a',
              'grouping' => '1',
            ],
          2 =>
            [
              'name' => 'city',
              'contact_type' => 'Individual',
              'column_number' => '1',
              'grouping' => '1',
            ],
          3 =>
            [
              'name' => 'contact_sub_type',
              'contact_type' => 'Individual',
              'column_number' => '2',
              'grouping' => '1',
            ],
          4 =>
            [
              'name' => 'custom_27',
              'contact_type' => 'Student',
              'column_number' => '3',
              'grouping' => '1',
            ],
          5 =>
            [
              'name' => 'current_employer',
              'contact_type' => 'Individual',
              'column_number' => '4',
              'grouping' => '1',
            ],
          6 =>
            [
              'name' => 'phone',
              'contact_type' => 'Individual',
              'column_number' => '5',
              'location_type_id' => '1',
              'phone_type_id' => '2',
              'grouping' => '1',
            ],
          7 =>
            [
              'name' => 'postal_code',
              'contact_type' => 'Individual',
              'column_number' => '6',
              'location_type_id' => '2',
              'grouping' => '1',
            ],
          8 =>
            [
              'name' => 'im',
              'contact_type' => 'Individual',
              'column_number' => '7',
              'location_type_id' => '1',
              'im_provider_id' => '1',
              'grouping' => '1',
            ],
          9 =>
            [
              'name' => 'url',
              'contact_type' => 'Individual',
              'column_number' => '8',
              'grouping' => '1',
            ],
          10 =>
            [
              'name' => 'phone',
              'contact_type' => 'Individual',
              'column_number' => '9',
              'location_type_id' => '5',
              'phone_type_id' => '1',
              'relationship_type_id' => 'Child of',
              'relationship_direction' => 'b_a',
              'grouping' => '1',
            ],
        ],
      ],
      [
        'params' => [
          'qfKey' => '054b561dbf948548f7883151f879fe72_533',
          'entryURL' => 'http://dmaster.local/civicrm/contact/search/builder?reset=1',
          'mapper' => [
            [
              ['Contact', 'addressee_id'],
              ['Individual', 'custom_2'],
              [''],
              [''],
              [''],
            ],
            [[''], [''], [''], [''], ['']],
            [[''], [''], [''], [''], ['']],
          ],
          'operator' => [
            ['IS NOT NULL', 'IS NULL', '', '', ''],
            ['', '', '', '', ''],
            ['', '', '', '', ''],
          ],
          'value' => [
            ['', '', '', '', ''],
            ['', '', '', '', ''],
            ['', '', '', '', ''],
          ],
          'task' => '12',
          'radio_ts' => 'ts_sel',
          'toggleSelect' => '1',
          'mark_x_98' => '1',
          'title' => 'mmo',

        ],
        'expected' => [
          1 => [
            'name' => 'addressee_id',
            'contact_type' => 'Contact',
            'column_number' => '0',
            'grouping' => '0',
            'operator' => 'IS NOT NULL',
          ],
          2 => [
            'name' => 'custom_2',
            'contact_type' => 'Individual',
            'column_number' => '1',
            'grouping' => '0',
            'operator' => 'IS NULL',
          ],
        ],
      ],
    ];
  }

  /**
   * Ensure getCreateMappingValues() doesn't return an error when there are spaces in the name.
   */
  public function testGetCreateMappingValues(): void {
    CRM_Core_BAO_Mapping::getCreateMappingValues("Import Multi value custom data");
  }

}
