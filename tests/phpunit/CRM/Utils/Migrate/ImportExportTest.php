<?php

/**
 * Class CRM_Utils_Migrate_ImportExportTest
 * @group headless
 */
class CRM_Utils_Migrate_ImportExportTest extends CiviUnitTestCase {
  protected $_apiversion;

  public function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
  }

  public function tearDown() {
    $tablesToTruncate = array(
      'civicrm_custom_group',
      'civicrm_custom_field',
    );
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  /**
   * Generate a list of basic XML test cases. Each test case creates a
   * custom-group and custom-field then compares the output to a pre-defined
   * XML file. Then, for each test-case, we reverse the process -- we
   * load the XML into a clean DB and see if it creates matching custom-group
   * and custom-field.
   */
  public function basicXmlTestCases() {
    // a small library which we use to describe test cases
    $fixtures = array();
    $fixtures['textField'] = array(
      'name' => 'test_textfield',
      'label' => 'Name1',
      'html_type' => 'Text',
      'data_type' => 'String',
      'default_value' => 'abc',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    );
    $fixtures['selectField'] = array(
      // custom_group_id
      'label' => 'Our select field',
      'html_type' => 'Select',
      'data_type' => 'String',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      // 'option_group_name' => 'our_select_field_20130818044104',
      'option_values' => array(
        array(
          'weight' => 1,
          'label' => 'Label1',
          'value' => 1,
          'is_active' => 1,
        ),
        array(
          'weight' => 2,
          'label' => 'Label2',
          'value' => 2,
          'is_active' => 1,
        ),
      ),
    );

    // the actual test cases
    $cases = array();

    $cases[] = array(
      // CustomGroup params
      array(
        'extends' => 'Contact',
        'title' => 'example',
      ),
      // CustomField params
      $fixtures['textField'],
      // expectedXmlFilePath
      __DIR__ . '/fixtures/Contact-text.xml',
    );

    /* @codingStandardsIgnoreStart
    $cases[] = array(
      // CustomGroup params
      array(
        'extends' => 'Contact',
        'title' => 'example',
      ),
      // CustomField params
      $fixtures['selectField'],
      // expectedXmlFilePath
      __DIR__ . '/fixtures/Contact-select.xml',
    );
    @codingStandardsIgnoreEnd */

    $cases[] = array(
      // CustomGroup params
      array(
        'extends' => 'Individual',
        'title' => 'example',
      ),
      // CustomField params
      $fixtures['textField'],
      // expectedXmlFilePath
      __DIR__ . '/fixtures/Individual-text.xml',
    );

    $cases[] = array(
      // CustomGroup params
      array(
        'extends' => 'Individual',
        'extends_entity_column_value' => array('Student'),
        'title' => 'example',
      ),
      // CustomField params
      $fixtures['textField'],
      // expectedXmlFilePath
      __DIR__ . '/fixtures/IndividualStudent-text.xml',
    );

    $cases[] = array(
      // CustomGroup params
      array(
        'extends' => 'Activity',
        'title' => 'example',
      ),
      // CustomField params
      $fixtures['textField'],
      // expectedXmlFilePath
      __DIR__ . '/fixtures/Activity-text.xml',
    );

    $cases[] = array(
      // CustomGroup params
      array(
        'extends' => 'Activity',
        'extends_entity_column_value' => [1],
        'title' => 'example',
      ),
      // CustomField params
      $fixtures['textField'],
      // expectedXmlFilePath
      __DIR__ . '/fixtures/ActivityMeeting-text.xml',
    );

    return $cases;
  }

  /**
   * Execute a basic XML test case. Each test case creates a custom-group and
   * custom-field then compares the output to a pre-defined XML file.
   *
   * @param array $customGroupParams
   * @param array $fieldParams
   * @param $expectedXmlFilePath
   * @dataProvider basicXmlTestCases
   */
  public function testBasicXMLExports($customGroupParams, $fieldParams, $expectedXmlFilePath) {
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_custom_group WHERE title = %1', array(
      1 => array($customGroupParams['title'], 'String'),
    ));
    $customGroup = $this->customGroupCreate($customGroupParams);
    $fieldParams['custom_group_id'] = $customGroup['id'];
    $customField = $this->callAPISuccess('custom_field', 'create', $fieldParams);

    $exporter = new CRM_Utils_Migrate_Export();
    $exporter->buildCustomGroups(array($customGroup['id']));
    // print $exporter->toXML();
    $this->assertEquals(file_get_contents($expectedXmlFilePath), $exporter->toXML());

    $this->callAPISuccess('custom_field', 'delete', array('id' => $customField['id']));
    $this->callAPISuccess('custom_group', 'delete', array('id' => $customGroup['id']));
  }

  /**
   * @param $expectCustomGroup
   * @param $expectCustomField
   * @param $inputXmlFilePath
   *
   * @throws CRM_Core_Exception
   * @dataProvider basicXmlTestCases
   */
  public function testBasicXMLImports($expectCustomGroup, $expectCustomField, $inputXmlFilePath) {
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_custom_group WHERE title = %1', array(
      1 => array($expectCustomGroup['title'], 'String'),
    ));

    $importer = new CRM_Utils_Migrate_Import();
    $importer->run($inputXmlFilePath);

    $customGroups = $this->callAPISuccess('custom_group', 'get', array('title' => $expectCustomGroup['title']));
    $this->assertEquals(1, $customGroups['count']);
    $customGroup = array_shift($customGroups['values']);
    foreach ($expectCustomGroup as $expectKey => $expectValue) {
      $this->assertEquals($expectValue, $customGroup[$expectKey]);
    }

    $customFields = $this->callAPISuccess('custom_field', 'get', array('label' => $expectCustomField['label']));
    $this->assertEquals(1, $customFields['count']);
    $customField = array_shift($customFields['values']);
    foreach ($expectCustomField as $expectKey => $expectValue) {
      $this->assertEquals($expectValue, $customField[$expectKey]);
    }
  }

}
