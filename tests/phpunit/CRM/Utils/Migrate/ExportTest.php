<?php
require_once 'CiviTest/CiviUnitTestCase.php';
class CRM_Utils_Migrate_ExportTest extends CiviUnitTestCase {
  protected $_apiversion;
  public $_eNoticeCompliant = TRUE;

  function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
  }

  function tearDown() {
    $tablesToTruncate = array(
      'civicrm_custom_group',
      'civicrm_custom_field',
    );
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  /**
   * Generate a list of basic XML test cases. Each test case creates a
   * custom-group and custom-field then compares the output to a pre-defined
   * XML file.
   */
  function basicXmlTestCases() {
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
        'title' => 'contact_text_example',
      ),
      // CustomField params
      $fixtures['textField'],
      // expectedXmlFilePath
      __DIR__ . '/fixtures/Contact-text.xml',
    );

    /*
    $cases[] = array(
      // CustomGroup params
      array(
        'extends' => 'Contact',
        'title' => 'contact_select_example',
      ),
      // CustomField params
      $fixtures['selectField'],
      // expectedXmlFilePath
      __DIR__ . '/fixtures/Contact-select.xml',
    );
    */

    $cases[] = array(
      // CustomGroup params
      array(
        'extends' => 'Individual',
        'title' => 'indiv_text_example',
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
        'extends_entity_column_value' => 'Student',
        'title' => 'indiv_text_example',
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
        'title' => 'activ_text_example',
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
        'extends_entity_column_value' => array_search('Meeting', CRM_Core_PseudoConstant::activityType()),
        'title' => 'activ_text_example',
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
   * @param $customGroupParams
   * @param $fieldParams
   * @param $expectedXmlFilePath
   * @dataProvider basicXmlTestCases
   */
  function testBasicXMLExports($customGroupParams, $fieldParams, $expectedXmlFilePath) {
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
}