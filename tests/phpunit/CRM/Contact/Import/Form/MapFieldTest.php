<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 5                                                  |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2019                                |
+--------------------------------------------------------------------+
| This file is a part of CiviCRM.                                    |
|                                                                    |
| CiviCRM is free software; you can copy, modify, and distribute it  |
| under the terms of the GNU Affero General Public License           |
| Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
|                                                                    |
| CiviCRM is distributed in the hope that it will be useful, but     |
| WITHOUT ANY WARRANTY; without even the implied warranty of         |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
| See the GNU Affero General Public License for more details.        |
|                                                                    |
| You should have received a copy of the GNU Affero General Public   |
| License and the CiviCRM Licensing Exception along                  |
| with this program; if not, contact CiviCRM LLC                     |
| at info[AT]civicrm[DOT]org. If you have questions about the        |
| GNU Affero General Public License or the licensing of CiviCRM,     |
| see the CiviCRM license FAQ at http://civicrm.org/licensing        |
+--------------------------------------------------------------------+
 */

/**
 * @file
 * File for the CRM_Contact_Import_Form_MapFieldTest class.
 */

/**
 *  Test contact import map field.
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_Import_Form_MapFieldTest extends CiviUnitTestCase {

  use CRM_Contact_Import_MetadataTrait;
  use CRMTraits_Custom_CustomDataTrait;

  /**
   * Map field form.
   *
   * @var CRM_Contact_Import_Form_MapField
   */
  protected $form;

  /**
   * Test the form loads without error / notice and mappings are assigned.
   *
   * (Added in conjunction with fixed noting on mapping assignment).
   *
   * @dataProvider getSubmitData
   *
   * @param array $params
   * @param array $mapper
   * @param array $expecteds
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSubmit($params, $mapper, $expecteds = []) {
    CRM_Core_DAO::executeQuery('CREATE TABLE IF NOT EXISTS civicrm_import_job_xxx (`nada` text, `first_name` text, `last_name` text, `address` text) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci');
    $form = $this->getFormObject('CRM_Contact_Import_Form_MapField');
    /* @var CRM_Contact_Import_Form_MapField $form */
    $form->set('contactType', CRM_Import_Parser::CONTACT_INDIVIDUAL);
    $form->_columnNames = ['nada', 'first_name', 'last_name', 'address'];
    $form->set('importTableName', 'civicrm_import_job_xxx');
    $form->preProcess();
    $form->submit($params, $mapper);

    CRM_Core_DAO::executeQuery("DROP TABLE civicrm_import_job_xxx");
    if (!empty($expecteds)) {
      foreach ($expecteds as $expected) {
        $result = $this->callAPISuccess($expected['entity'], 'get', array_merge($expected['values'], ['sequential' => 1]));
        $this->assertEquals($expected['count'], $result['count']);
        if (isset($expected['result'])) {
          foreach ($expected['result'] as $key => $expectedValues) {
            foreach ($expectedValues as $valueKey => $value) {
              $this->assertEquals($value, $result['values'][$key][$valueKey]);
            }
          }
        }
      }
    }
    $this->quickCleanup(['civicrm_mapping', 'civicrm_mapping_field']);
  }

  /**
   * Get data to pass through submit function.
   *
   * @return array
   */
  public function getSubmitData() {
    return [
      'basic_data' => [
        [
          'saveMappingName' => '',
          'saveMappingDesc' => '',
        ],
        [
          0 => [0 => 'do_not_import'],
          1 => [0 => 'first_name'],
          2 => [0 => 'last_name'],
          3 => [0 => 'street_address', 1 => 2],
        ],
      ],
      'save_mapping' => [
        [
          'saveMappingName' => 'new mapping',
          'saveMappingDesc' => 'save it',
          'saveMapping' => 1,
        ],
        [
          0 => [0 => 'do_not_import'],
          1 => [0 => 'first_name'],
          2 => [0 => 'last_name'],
          3 => [0 => 'street_address', 1 => 2],
        ],
        [
          ['entity' => 'mapping', 'count' => 1, 'values' => ['name' => 'new mapping']],
          [
            'entity' =>
            'mapping_field',
            'count' => 4,
            'values' => [],
            'result' => [
              0 => ['name' => '- do not import -'],
              1 => ['name' => 'First Name'],
              2 => ['name' => 'Last Name'],
              3 => ['name' => 'Street Address', 'location_type_id' => 2],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Instantiate form object
   *
   * @param string $class
   * @param array $formValues
   * @param string $pageName
   * @return \CRM_Core_Form
   * @throws \CRM_Core_Exception
   */
  public function getFormObject($class, $formValues = [], $pageName = '') {
    $form = parent::getFormObject($class);
    $contactFields = CRM_Contact_BAO_Contact::importableFields();
    $fields = [];
    foreach ($contactFields as $name => $field) {
      $fields[$name] = $field['title'];
    }
    $form->set('fields', $fields);
    return $form;
  }

  /**
   * Test the function that loads saved field mappings.
   *
   * @dataProvider mapFieldDataProvider
   *
   * @param array $fieldSpec
   * @param string $expectedJS
   * @param array $expectedDefaults
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testLoadSavedMapping($fieldSpec, $expectedJS, $expectedDefaults) {
    $this->setUpMapFieldForm();

    $mapping = $this->callAPISuccess('Mapping', 'create', ['name' => 'my test']);
    $this->callAPISuccess('MappingField', 'create', array_merge(['mapping_id' => $mapping['id']], $fieldSpec));
    $result = $this->loadSavedMapping($this->form, $mapping['id'], $fieldSpec['column_number']);
    $this->assertEquals($expectedJS, $result['js']);
    $this->assertEquals($expectedDefaults, $result['defaults']);
  }

  /**
   * Tests the 'final' methods for loading  the direct mapping.
   *
   * In conjunction with testing our existing  function this  tests the methods we want to migrate to
   * to  clean it up.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testLoadSavedMappingDirect() {
    $this->entity = 'Contact';
    $this->createCustomGroupWithFieldOfType(['title' => 'My Field']);
    $this->setUpMapFieldForm();
    $mapping = $this->callAPISuccess('Mapping', 'create', ['name' => 'my test', 'label' => 'Special custom']);
    foreach ([
      [
        'name' => 'Addressee',
        'column_number' => '0',
      ],
      [
        'name' => 'Postal Greeting',
        'column_number' => '1',
      ],
      [
        'name' => 'Phone',
        'column_number' => '2',
        'location_type_id' => '1',
        'phone_type_id' => '1',
      ],
      [
        'name' => 'Street Address',
        'column_number' => '3',
      ],
      [
        'name' => 'Enter text here :: My Field',
        'column_number' => '4',
      ],
      [
        'name' => 'Street Address',
        'column_number' => '5',
        'location_type_id' => '1',
      ],
      [
        'name' => 'City',
        'column_number' => '6',
        'location_type_id' => '1',
      ],
      [
        'name' => 'State Province',
        'column_number' => '7',
        'relationship_type_id' => 4,
        'relationship_direction' => 'a_b',
        'location_type_id' => '1',
      ],
      [
        'name' => 'Url',
        'column_number' => '8',
        'relationship_type_id' => 4,
        'relationship_direction' => 'a_b',
        'website_type_id' => 2,
      ],
      [
        'name' => 'Phone',
        'column_number' => '9',
        'relationship_type_id' => 4,
        'location_type_id' => '1',
        'relationship_direction' => 'a_b',
        'phone_type_id' => 2,
      ],
      [
        'name' => 'Phone',
        'column_number' => '10',
        'location_type_id' => '1',
        'phone_type_id' => '3',
      ],
    ] as $mappingField) {
      $this->callAPISuccess('MappingField', 'create', array_merge([
        'mapping_id' => $mapping['id'],
        'grouping' => 1,
        'contact_type' => 'Individual',
      ], $mappingField));
    }
    $processor = new CRM_Import_ImportProcessor();
    $processor->setMappingID($mapping['id']);
    $processor->setMetadata($this->getContactImportMetadata());
    $this->assertEquals(3, $processor->getPhoneOrIMTypeID(10));
    $this->assertEquals(3, $processor->getPhoneTypeID(10));
    $this->assertEquals(1, $processor->getLocationTypeID(10));
    $this->assertEquals(2, $processor->getWebsiteTypeID(8));
    $this->assertEquals('4_a_b', $processor->getRelationshipKey(9));
    $this->assertEquals('addressee', $processor->getFieldName(0));
    $this->assertEquals('street_address', $processor->getFieldName(3));
    $this->assertEquals($this->getCustomFieldName('text'), $processor->getFieldName(4));
    $this->assertEquals('url', $processor->getFieldName(8));

    $processor->setContactTypeByConstant(CRM_Import_Parser::CONTACT_HOUSEHOLD);
    $this->assertEquals('Household', $processor->getContactType());
  }

  /**
   * Get data for map field tests.
   */
  public function mapFieldDataProvider() {
    return [
      [
        ['name' => 'First Name', 'contact_type' => 'Individual', 'column_number' => 1],
        "document.forms.MapField['mapper[1][1]'].style.display = 'none';
document.forms.MapField['mapper[1][2]'].style.display = 'none';
document.forms.MapField['mapper[1][3]'].style.display = 'none';\n",
        ['mapper[1]' => ['first_name', 0, NULL]],
      ],
      [
        ['name' => 'Phone', 'contact_type' => 'Individual', 'column_number' => 8, 'phone_type_id' => 1, 'location_type_id' => 2],
        "document.forms.MapField['mapper[8][3]'].style.display = 'none';\n",
        ['mapper[8]' => ['phone', 2, 1]],
      ],
      [
        ['name' => 'IM Screen Name', 'contact_type' => 'Individual', 'column_number' => 0, 'im_provider_id' => 1, 'location_type_id' => 2],
        "document.forms.MapField['mapper[0][3]'].style.display = 'none';\n",
        ['mapper[0]' => ['im', 2, 1]],
      ],
      [
        ['name' => 'Website', 'contact_type' => 'Individual', 'column_number' => 0, 'website_type_id' => 1],
        "document.forms.MapField['mapper[0][2]'].style.display = 'none';
document.forms.MapField['mapper[0][3]'].style.display = 'none';\n",
        ['mapper[0]' => ['url', 1]],
      ],
      [
        // Yes, the relationship mapping really does use url whereas non relationship uses website because... legacy
        ['name' => 'Url', 'contact_type' => 'Individual', 'column_number' => 0, 'website_type_id' => 1, 'relationship_type_id' => 1, 'relationship_direction' => 'a_b'],
        "document.forms.MapField['mapper[0][3]'].style.display = 'none';\n",
        ['mapper[0]' => ['1_a_b', 'url', 1]],
      ],
      [
        ['name' => 'Phone', 'contact_type' => 'Individual', 'column_number' => 0, 'phone_type_id' => 1, 'relationship_type_id' => 1, 'relationship_direction' => 'b_a'],
        '',
        ['mapper[0]' => ['1_b_a', 'phone', 'Primary', 1]],
      ],
    ];
  }

  /**
   * Test the MapField function getting defaults from column names.
   *
   * @dataProvider getHeaderMatchDataProvider
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function testDefaultFromColumnNames($columnHeader, $mapsTo) {
    $this->setUpMapFieldForm();
    $this->assertEquals($mapsTo, $this->form->defaultFromColumnName($columnHeader, $this->getHeaderPatterns()));
  }

  /**
   * Get data to use for default from column names.
   *
   * @return array
   */
  public function getHeaderMatchDataProvider() {
    return [
      ['Contact Id', 'id'],
      ['Contact ID', 'id'],
      ['contact id', 'id'],
      ['contact_id', 'id'],
      // Yes, really... id wins the day here.
      ['external id', 'id'],
      ['external ident', 'external_identifier'],
      ['external idg', 'external_identifier'],
    ];
  }

  /**
   * Wrapper for loadSavedMapping.
   *
   * This signature of the function we are calling is funky as a new extraction & will be refined.
   *
   * @param \CRM_Contact_Import_Form_MapField $form
   *
   * @param int $mappingID
   * @param int $columnNumber
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected function loadSavedMapping($form, $mappingID, $columnNumber) {
    list($mappingName) = CRM_Core_BAO_Mapping::getMappingFields($mappingID, TRUE);

    //get loaded Mapping Fields
    $mappingName = CRM_Utils_Array::value(1, $mappingName);
    $defaults = [];

    $js = '';
    $hasColumnNames = TRUE;
    $processor = new CRM_Import_ImportProcessor();
    $processor->setMappingID($mappingID);
    $processor->setFormName('document.forms.MapField');
    $processor->setMetadata($this->getContactImportMetadata());
    $processor->setContactTypeByConstant(CRM_Import_Parser::CONTACT_INDIVIDUAL);

    $return = $form->loadSavedMapping($processor, $mappingName, $columnNumber, $defaults, $js, $hasColumnNames);
    return ['defaults' => $return[0], 'js' => $return[1]];
  }

  /**
   * Set up the mapping form.
   *
   * @throws \CRM_Core_Exception
   */
  private function setUpMapFieldForm() {
    $this->form = $this->getFormObject('CRM_Contact_Import_Form_MapField');
    $this->form->set('contactType', CRM_Import_Parser::CONTACT_INDIVIDUAL);
  }

}
