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
  public function tearDown() {
    $this->quickCleanup(['civicrm_mapping_field', 'civicrm_mapping']);
    parent::tearDown();
  }

  /**
   * Test calling saveMapping.
   *
   * @dataProvider getMappingMaps
   */
  public function testSaveMappingFields($params, $expected) {
    $mapping = $this->callAPISuccess('Mapping', 'create', ['name' => 'teest']);
    CRM_Core_BAO_Mapping::saveMappingFields($params, $mapping['id']);
    $saved = $this->callAPISuccess('MappingField', 'get', ['mapping_id' => $mapping['id']])['values'];
    $this->assertEquals($expected, $saved);
  }

  /**
   * Get data for mapping test.
   *
   * @return array
   */
  public function getMappingMaps() {
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
                ['Individual', '10_b_a', 'id'],
                ['Individual', 'city', ' '],
                ['Individual', 'contact_sub_type'],
                ['Student', 'custom_27'],
                ['Individual', 'current_employer'],
                ['Individual', 'phone', '1', '2'],
                ['Individual', 'postal_code', '2'],
                ['Individual', 'im', '1', '1'],
                ['Individual', 'url'],
                ['Individual', '1_b_a', 'phone', '5', '1'],
              ],
          ],
        ],
        'expected' => $expected = [
          1 =>
            [
              'id' => '1',
              'mapping_id' => '1',
              'name' => 'id',
              'contact_type' => 'Individual',
              'column_number' => '0',
              'relationship_type_id' => '10',
              'relationship_direction' => 'b_a',
              'grouping' => '1',
            ],
          2 =>
            [
              'id' => '2',
              'mapping_id' => '1',
              'name' => 'city',
              'contact_type' => 'Individual',
              'column_number' => '1',
              'grouping' => '1',
            ],
          3 =>
            [
              'id' => '3',
              'mapping_id' => '1',
              'name' => 'contact_sub_type',
              'contact_type' => 'Individual',
              'column_number' => '2',
              'grouping' => '1',
            ],
          4 =>
            [
              'id' => '4',
              'mapping_id' => '1',
              'name' => 'custom_27',
              'contact_type' => 'Student',
              'column_number' => '3',
              'grouping' => '1',
            ],
          5 =>
            [
              'id' => '5',
              'mapping_id' => '1',
              'name' => 'current_employer',
              'contact_type' => 'Individual',
              'column_number' => '4',
              'grouping' => '1',
            ],
          6 =>
            [
              'id' => '6',
              'mapping_id' => '1',
              'name' => 'phone',
              'contact_type' => 'Individual',
              'column_number' => '5',
              'location_type_id' => '1',
              'phone_type_id' => '2',
              'grouping' => '1',
            ],
          7 =>
            [
              'id' => '7',
              'mapping_id' => '1',
              'name' => 'postal_code',
              'contact_type' => 'Individual',
              'column_number' => '6',
              'location_type_id' => '2',
              'grouping' => '1',
            ],
          8 =>
            [
              'id' => '8',
              'mapping_id' => '1',
              'name' => 'im',
              'contact_type' => 'Individual',
              'column_number' => '7',
              'location_type_id' => '1',
              'im_provider_id' => '1',
              'grouping' => '1',
            ],
          9 =>
            [
              'id' => '9',
              'mapping_id' => '1',
              'name' => 'url',
              'contact_type' => 'Individual',
              'column_number' => '8',
              'grouping' => '1',
            ],
          10 =>
            [
              'id' => '10',
              'mapping_id' => '1',
              'name' => 'phone',
              'contact_type' => 'Individual',
              'column_number' => '9',
              'location_type_id' => '5',
              'phone_type_id' => '1',
              'relationship_type_id' => '1',
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
            'id' => '1',
            'mapping_id' => '1',
            'name' => 'addressee_id',
            'contact_type' => 'Contact',
            'column_number' => '0',
            'grouping' => '0',
            'operator' => 'IS NOT NULL',
          ],
          2 => [
            'id' => '2',
            'mapping_id' => '1',
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

}
