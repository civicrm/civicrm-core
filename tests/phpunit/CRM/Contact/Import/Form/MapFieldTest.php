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

use Civi\Api4\MappingField;
use Civi\Api4\UserJob;

/**
 *  Test contact import map field.
 *
 * @package CiviCRM
 * @group headless
 * @group import
 */
class CRM_Contact_Import_Form_MapFieldTest extends CiviUnitTestCase {

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
    $this->quickCleanup(['civicrm_mapping', 'civicrm_mapping_field', 'civicrm_user_job', 'civicrm_queue'], TRUE);
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
   * @param array $mapper Mapping as entered on MapField form.
   *   e.g [['first_name']['email', 1]].
   *   {@see \CRM_Contact_Import_Parser_Contact::getMappingFieldFromMapperInput}
   * @param array $expecteds
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmit(array $params, array $mapper, array $expecteds = []): void {
    $form = $this->getMapFieldFormObject(array_merge($params, ['mapper' => $mapper]));
    $form->preProcess();
    $form->postProcess();

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
              0 => ['name' => 'do_not_import'],
              1 => ['name' => 'first_name'],
              2 => ['name' => 'last_name'],
              3 => ['name' => 'street_address', 'location_type_id' => 2],
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
   * @throws \CRM_Core_Exception
   */
  public function getMapFieldFormObject(array $submittedValues = []): CRM_Contact_Import_Form_MapField {
    CRM_Core_DAO::executeQuery('CREATE TABLE IF NOT EXISTS civicrm_tmp_d_import_job_xxx (`nada` text, `first_name` text, `last_name` text, `address` text) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci');
    $submittedValues = array_merge([
      'contactType' => 'Individual',
      'contactSubType' => '',
      'dataSource' => 'CRM_Import_DataSource_SQL',
      'sqlQuery' => 'SELECT * FROM civicrm_tmp_d_import_job_xxx',
      'dedupe_rule_id' => '',
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_UPDATE,
    ], $submittedValues);
    $userJobID = UserJob::create()->setValues([
      'metadata' => [
        'submitted_values' => $submittedValues,
      ],
      'status_id:name' => 'draft',
      'job_type' => 'contact_import',
    ])->execute()->first()['id'];

    $dataSource = new CRM_Import_DataSource_SQL($userJobID);
    $null = NULL;
    /** @var CRM_Contact_Import_Form_MapField $form */
    $form = $this->getFormObject('CRM_Contact_Import_Form_MapField', $submittedValues);
    $form->set('user_job_id', $userJobID);
    $dataSource->initialize();

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
   * @throws \CRM_Core_Exception
   */
  public function testLoadSavedMapping(array $fieldSpec, string $expectedJS, array $expectedDefaults): void {
    $mapping = $this->callAPISuccess('Mapping', 'create', ['name' => 'my test']);
    $this->callAPISuccess('MappingField', 'create', array_merge(['mapping_id' => $mapping['id']], $fieldSpec));
    $this->form = $this->form = $this->getMapFieldFormObject(['savedMapping' => $mapping['id'], 'sqlQuery' => 'SELECT nada FROM civicrm_tmp_d_import_job_xxx']);
    $expectedJS = "<script type='text/javascript'>
" . $expectedJS . '</script>';

    $this->assertEquals($expectedJS, trim((string) CRM_Core_Smarty::singleton()->getTemplateVars('initHideBoxes')));
    $this->assertEquals($expectedDefaults, $this->form->_defaultValues);
  }

  /**
   * Tests the 'final' methods for loading  the direct mapping.
   *
   * In conjunction with testing our existing  function this  tests the methods we want to migrate to
   * to  clean it up.
   *
   * @throws \CRM_Core_Exception
   */
  public function testLoadSavedMappingDirect(): void {
    $mapping = $this->storeComplexMapping();
    $this->setUpMapFieldForm();
    $parser = new CRM_Contact_Import_Parser_Contact();
    $parser->setUserJobID($this->form->getUserJobID());
    $processor = new CRM_Import_ImportProcessor();
    $processor->setMappingID($mapping['id']);
    $processor->setMetadata($parser->getFieldsMetadata());
    $this->assertEquals(3, $processor->getPhoneOrIMTypeID(10));
    $this->assertEquals(3, $processor->getPhoneTypeID(10));
    $this->assertEquals(1, $processor->getLocationTypeID(10));
    $this->assertEquals(2, $processor->getWebsiteTypeID(8));
    $this->assertEquals('4_a_b', $processor->getRelationshipKey(9));
    $this->assertEquals('addressee', $processor->getFieldName(0));
    $this->assertEquals('street_address', $processor->getFieldName(3));
    $this->assertEquals($this->getCustomFieldName('text'), $processor->getFieldName(4));
    $this->assertEquals('url', $processor->getFieldName(8));
  }

  /**
   * Get data for map field tests.
   */
  public function mapFieldDataProvider(): array {
    return [
      [
        ['name' => 'first_name', 'contact_type' => 'Individual', 'column_number' => 0],
        "swapOptions(document.forms.MapField, 'mapper[0]', 0, 4, 'hs_mapper_0_');\n",
        ['mapper[0]' => ['first_name']],
      ],
      [
        ['name' => 'phone', 'contact_type' => 'Individual', 'column_number' => 0, 'phone_type_id' => 1, 'location_type_id' => 2],
        "swapOptions(document.forms.MapField, 'mapper[0]', 2, 4, 'hs_mapper_0_');\n",
        ['mapper[0]' => ['phone', 2, 1]],
      ],
      [
        ['name' => 'im', 'contact_type' => 'Individual', 'column_number' => 0, 'im_provider_id' => 1, 'location_type_id' => 2],
        "swapOptions(document.forms.MapField, 'mapper[0]', 2, 4, 'hs_mapper_0_');\n",
        ['mapper[0]' => ['im', 2, 1]],
      ],
      [
        ['name' => 'url', 'contact_type' => 'Individual', 'column_number' => 0, 'website_type_id' => 1],
        "swapOptions(document.forms.MapField, 'mapper[0]', 1, 4, 'hs_mapper_0_');\n",
        ['mapper[0]' => ['url', 1]],
      ],
      [
        // Yes, the relationship mapping really does use url whereas non relationship uses website because... legacy
        ['name' => 'url', 'contact_type' => 'Individual', 'column_number' => 0, 'website_type_id' => 1, 'relationship_type_id' => 1, 'relationship_direction' => 'a_b'],
        "swapOptions(document.forms.MapField, 'mapper[0]', 2, 4, 'hs_mapper_0_');\n",
        ['mapper[0]' => ['1_a_b', 'url', 1]],
      ],
      [
        ['name' => 'phone', 'contact_type' => 'Individual', 'column_number' => 0, 'phone_type_id' => 1, 'relationship_type_id' => 1, 'relationship_direction' => 'b_a'],
        '',
        ['mapper[0]' => ['1_b_a', 'phone', 'Primary', 1]],
      ],
      [
        ['name' => 'do_not_import', 'contact_type' => 'Individual', 'column_number' => 0],
        "swapOptions(document.forms.MapField, 'mapper[0]', 0, 4, 'hs_mapper_0_');\n",
        ['mapper[0]' => ['do_not_import']],
      ],
    ];
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
   * @throws \CRM_Core_Exception
   */
  protected function loadSavedMapping(int $mappingID, int $columnNumber): array {
    $processor = new CRM_Import_ImportProcessor();
    $processor->setMappingID($mappingID);
    $processor->setFormName('document.forms.MapField');
    $parser = new CRM_Contact_Import_Parser_Contact();
    $parser->setUserJobID($this->form->getUserJobID());
    $processor->setMetadata($parser->getFieldsMetadata());
    $processor->setContactType('Individual');

    $defaults = [];
    $defaults["mapper[$columnNumber]"] = $processor->getSavedQuickformDefaultsForColumn($columnNumber);

    return ['defaults' => $defaults];
  }

  /**
   * Set up the mapping form.
   *
   * @throws \CRM_Core_Exception
   */
  private function setUpMapFieldForm(): void {
    $this->form = $this->getMapFieldFormObject();
    $this->form->set('contactType', 'Individual');
  }

  /**
   * Tests the routing used in the 5.50 upgrade script to stop using labels...
   *
   * @throws \CRM_Core_Exception
   */
  public function testConvertFields(): void {
    $mapping = $this->storeComplexMapping(TRUE);
    CRM_Import_ImportProcessor::convertSavedFields();
    $updatedMapping = MappingField::get()
      ->addWhere('mapping_id', '=', $mapping['id'])
      ->addSelect('id', 'name')->execute();

    $expected = [
      0 => 'addressee',
      1 => 'postal_greeting',
      2 => 'phone',
      3 => 'street_address',
      4 => 'custom_1',
      5 => 'street_address',
      6 => 'city',
      7 => 'state_province',
      8 => 'url',
      9 => 'phone',
      10 => 'phone',
    ];
    foreach ($updatedMapping as $index => $mappingField) {
      $this->assertEquals($expected[$index], $mappingField['name']);
    }
  }

  /**
   * Store a mapping with a complex set of fields.
   *
   * @param bool $legacyMode
   *
   * @return array
   */
  private function storeComplexMapping(bool $legacyMode = FALSE): array {
    $this->createCustomGroupWithFieldOfType(['title' => 'My Field']);
    $mapping = $this->callAPISuccess('Mapping', 'create', [
      'name' => 'my test',
      'label' => 'Special custom',
      'mapping_type_id' => 'Import Contact',
    ]);
    foreach (
      [
        [
          'name' => $legacyMode ? 'Addressee' : 'addressee',
          'column_number' => '0',
        ],
        [
          'name' => $legacyMode ? 'Postal Greeting' : 'postal_greeting',
          'column_number' => '1',
        ],
        [
          'name' => $legacyMode ? 'Phone' : 'phone',
          'column_number' => '2',
          'location_type_id' => '1',
          'phone_type_id' => '1',
        ],
        [
          'name' => $legacyMode ? 'Street Address' : 'street_address',
          'column_number' => '3',
        ],
        [
          'name' => $legacyMode ? 'Enter text here :: My Field' : $this->getCustomFieldName('text'),
          'column_number' => '4',
        ],
        [
          'name' => $legacyMode ? 'Street Address' : 'street_address',
          'column_number' => '5',
          'location_type_id' => '1',
        ],
        [
          'name' => $legacyMode ? 'City' : 'city',
          'column_number' => '6',
          'location_type_id' => '1',
        ],
        [
          'name' => $legacyMode ? 'State Province' : 'state_province',
          'column_number' => '7',
          'relationship_type_id' => 4,
          'relationship_direction' => 'a_b',
          'location_type_id' => '1',
        ],
        [
          'name' => $legacyMode ? 'Url' : 'url',
          'column_number' => '8',
          'relationship_type_id' => 4,
          'relationship_direction' => 'a_b',
          'website_type_id' => 2,
        ],
        [
          'name' => $legacyMode ? 'Phone' : 'phone',
          'column_number' => '9',
          'relationship_type_id' => 4,
          'location_type_id' => '1',
          'relationship_direction' => 'a_b',
          'phone_type_id' => 2,
        ],
        [
          'name' => $legacyMode ? 'Phone' : 'phone',
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
    return $mapping;
  }

}
