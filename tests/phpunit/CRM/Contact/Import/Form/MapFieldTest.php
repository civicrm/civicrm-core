<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This code is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * @file
 * File for the CRM_Contact_Import_Form_MapFieldTest class.
 */

use Civi\Api4\UserJob;

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
   * Delete any saved mapping config.
   */
  public function tearDown(): void {
    $this->quickCleanup(['civicrm_mapping', 'civicrm_mapping_field']);
    parent::tearDown();
  }

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
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSubmit(array $params, array $mapper, array $expecteds = []): void {
    $form = $this->getMapFieldFormObject();
    $form->set('contactType', CRM_Import_Parser::CONTACT_INDIVIDUAL);
    $form->preProcess();
    $form->submit($params, $mapper);

    CRM_Core_DAO::executeQuery('DROP TABLE civicrm_tmp_d_import_job_xxx');
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
  public function getSubmitData(): array {
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
   * Instantiate MapField form object
   *
   * @param array $submittedValues
   *   Values that would be submitted by the user.
   *   Some defaults are provided.
   *
   * @return \CRM_Contact_Import_Form_MapField
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function getMapFieldFormObject(array $submittedValues = []): CRM_Contact_Import_Form_MapField {
    CRM_Core_DAO::executeQuery('CREATE TABLE IF NOT EXISTS civicrm_tmp_d_import_job_xxx (`nada` text, `first_name` text, `last_name` text, `address` text) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci');
    $submittedValues = array_merge([
      'contactType' => CRM_Import_Parser::CONTACT_INDIVIDUAL,
      'contactSubType' => '',
      'dataSource' => 'CRM_Import_DataSource_SQL',
      'sqlQuery' => 'SELECT * FROM civicrm_tmp_d_import_job_xxx',
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_UPDATE,
    ], $submittedValues);
    $userJobID = UserJob::create()->setValues([
      'metadata' => [
        'submitted_values' => $submittedValues,
      ],
      'status_id:name' => 'draft',
      'type_id:name' => 'contact_import',
    ])->execute()->first()['id'];

    $dataSource = new CRM_Import_DataSource_SQL($userJobID);
    $null = NULL;
    /* @var CRM_Contact_Import_Form_MapField $form */
    $form = $this->getFormObject('CRM_Contact_Import_Form_MapField', $submittedValues);
    $form->set('user_job_id', $userJobID);
    $dataSource->postProcess($submittedValues, $null, $form);

    $contactFields = CRM_Contact_BAO_Contact::importableFields();
    $fields = [];
    foreach ($contactFields as $name => $field) {
      $fields[$name] = $field['title'];
    }
    $form->set('fields', $fields);
    $form->buildForm();
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
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function testLoadSavedMapping(array $fieldSpec, string $expectedJS, array $expectedDefaults): void {
    $mapping = $this->callAPISuccess('Mapping', 'create', ['name' => 'my test']);
    $this->callAPISuccess('MappingField', 'create', array_merge(['mapping_id' => $mapping['id']], $fieldSpec));
    $this->form = $this->form = $this->getMapFieldFormObject(['savedMapping' => $mapping['id'], 'sqlQuery' => 'SELECT nada FROM civicrm_tmp_d_import_job_xxx']);
    $expectedJS = "<script type='text/javascript'>
" . $expectedJS . '</script>';

    $this->assertEquals($expectedJS, trim((string) CRM_Core_Smarty::singleton()->get_template_vars('initHideBoxes')));
    $this->assertEquals($expectedDefaults, $this->form->_defaultValues);
  }

  /**
   * Tests the 'final' methods for loading  the direct mapping.
   *
   * In conjunction with testing our existing  function this  tests the methods we want to migrate to
   * to  clean it up.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testLoadSavedMappingDirect(): void {
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
  public function mapFieldDataProvider(): array {
    return [
      [
        ['name' => 'First Name', 'contact_type' => 'Individual', 'column_number' => 0],
        "document.forms.MapField['mapper[0][1]'].style.display = 'none';
document.forms.MapField['mapper[0][2]'].style.display = 'none';
document.forms.MapField['mapper[0][3]'].style.display = 'none';\n",
        ['mapper[0]' => ['first_name', 0, NULL]],
      ],
      [
        ['name' => 'Phone', 'contact_type' => 'Individual', 'column_number' => 0, 'phone_type_id' => 1, 'location_type_id' => 2],
        "document.forms.MapField['mapper[0][3]'].style.display = 'none';\n",
        ['mapper[0]' => ['phone', 2, 1]],
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
      [
        ['name' => '- do not import -', 'contact_type' => 'Individual', 'column_number' => 0],
        "document.forms.MapField['mapper[0][1]'].style.display = 'none';
document.forms.MapField['mapper[0][2]'].style.display = 'none';
document.forms.MapField['mapper[0][3]'].style.display = 'none';\n",
        ['mapper[0]' => []],
      ],
    ];
  }

  /**
   * Test the MapField function getting defaults from column names.
   *
   * @dataProvider getHeaderMatchDataProvider
   *
   * @param $columnHeader
   * @param $mapsTo
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function testDefaultFromColumnNames($columnHeader, $mapsTo): void {
    $this->setUpMapFieldForm();
    $this->assertEquals($mapsTo, $this->form->defaultFromColumnName($columnHeader));
  }

  /**
   * Get data to use for default from column names.
   *
   * @return array
   */
  public function getHeaderMatchDataProvider(): array {
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
   * This is accessed by virtue of the MetaDataTrait being included.
   *
   * The use of the metadataTrait came from a transitional refactor
   * but it probably should be phased out again.
   */
  protected function getContactType(): string {
    return $this->_contactType ?? 'Individual';
  }

  /**
   * This is accessed by virtue of the MetaDataTrait being included.
   *
   * The use of the metadataTrait came from a transitional refactor
   * but it probably should be phased out again.
   */
  protected function getContactSubType(): string {
    return $this->_contactSubType ?? '';
  }

  /**
   * Wrapper for loadSavedMapping.
   *
   * This signature of the function we are calling is funky as a new extraction & will be refined.
   *
   * @param int $mappingID
   * @param int $columnNumber
   *
   * @return array
   *
   * @throws \CiviCRM_API3_Exception
   */
  protected function loadSavedMapping(int $mappingID, int $columnNumber): array {
    $processor = new CRM_Import_ImportProcessor();
    $processor->setMappingID($mappingID);
    $processor->setFormName('document.forms.MapField');
    $processor->setMetadata($this->getContactImportMetadata());
    $processor->setContactTypeByConstant(CRM_Import_Parser::CONTACT_INDIVIDUAL);

    $defaults = [];
    $defaults["mapper[$columnNumber]"] = $processor->getSavedQuickformDefaultsForColumn($columnNumber);
    $js = $processor->getQuickFormJSForField($columnNumber);

    return ['defaults' => $defaults, 'js' => $js];
  }

  /**
   * Set up the mapping form.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  private function setUpMapFieldForm(): void {
    $this->form = $this->getMapFieldFormObject();
    $this->form->set('contactType', CRM_Import_Parser::CONTACT_INDIVIDUAL);
  }

}
