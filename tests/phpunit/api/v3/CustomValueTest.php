<?php
// $Id$

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_CustomValueTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $individual;
  protected $params;
  protected $ids;
  public $_eNoticeCompliant = TRUE;
  public $DBResetRequired = FALSE;

  function setUp() {
    parent::setUp();
    $this->_apiversion = 3;
    $this->individual  = $this->individualCreate();
    $this->params      = array(
      'version' => $this->_apiversion,
      'entity_id' => $this->individual,
      'debug' => 1,
    );
    $this->ids['single'] = $this->entityCustomGroupWithSingleFieldCreate('mySingleField', 'Contacts');
    $this->ids['multi']  = $this->CustomGroupMultipleCreateWithFields();
    $this->ids['multi2'] = $this->CustomGroupMultipleCreateWithFields(array('title' => 'group2'));
  }

  function tearDown() {
    $tablesToTruncate = array(
      'civicrm_email',
      'civicrm_custom_field',
      'civicrm_custom_group',
      'civicrm_contact',
    );

    // true tells quickCleanup to drop any tables that might have been created in the test
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  public function testCreateCustomValue() {

    $params = array(
      'custom_' . $this->ids['single']['custom_field_id'] => 'customString') + $this->params;

    $result = civicrm_api('custom_value', 'create', $params);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $result = civicrm_api('custom_value', 'get', $params);
  }

  public function testGetMultipleCustomValues() {

    $description = "/*this demonstrates the use of CustomValue get";

    $params = array(
      'first_name' => 'abc3',
      'last_name' => 'xyz3',
      'contact_type' => 'Individual',
      'email' => 'man3@yahoo.com',
      'version' => $this->_apiversion,
      'custom_' . $this->ids['single']['custom_field_id'] => "value 1",
      'custom_' . $this->ids['multi']['custom_field_id'][0] => "value 2",
      'custom_' . $this->ids['multi']['custom_field_id'][1] => "warm beer",
      'custom_' . $this->ids['multi']['custom_field_id'][2] => "fl* w*",
      'custom_' . $this->ids['multi2']['custom_field_id'][2] => "vegemite",
    );


    $result = civicrm_api('Contact', 'create', $params);
    $this->assertAPISuccess($result, __LINE__);
    $contact_id = $result['id'];
    $result = civicrm_api('Contact', 'create',
      array(
        'contact_type' => 'Individual',
        'id' => $contact_id,
        'version' => 3,
        'custom_' . $this->ids['multi']['custom_field_id'][0] => "value 3",
        'custom_' . $this->ids['multi2']['custom_field_id'][0] => "coffee",
        'custom_' . $this->ids['multi2']['custom_field_id'][1] => "value 4",
      )
    );

    $params = array(
      'id' => $result['id'], 'version' => 3,
      'entity_id' => $result['id'],
    );

    $result = civicrm_api('CustomValue', 'Get', $params);

    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description);
    $params['format.field_names'] = 1;
    $resultformatted = civicrm_api('CustomValue', 'Get', $params);
    $this->documentMe($params, $resultformatted, __FUNCTION__, __FILE__, "utilises field names", 'formatFieldName');
    // delete the contact
    civicrm_api('contact', 'delete', array('version' => 1, 'id' => $contact_id));

    $this->assertAPISuccess( $result);
    $this->assertEquals('coffee', $result['values'][$this->ids['multi2']['custom_field_id'][0]]['2'], "In line " . __LINE__);
    $this->assertEquals('coffee', $result['values'][$this->ids['multi2']['custom_field_id'][0]]['latest'], "In line " . __LINE__);
    $this->assertEquals($this->ids['multi2']['custom_field_id'][0], $result['values'][$this->ids['multi2']['custom_field_id'][0]]['id'], "In line " . __LINE__);
    $this->assertEquals('', $result['values'][$this->ids['multi2']['custom_field_id'][0]]['1'], "In line " . __LINE__);
    $this->assertEquals($contact_id, $result['values'][$this->ids['multi2']['custom_field_id'][0]]['entity_id'], "In line " . __LINE__);
    $this->assertEquals('value 1', $result['values'][$this->ids['single']['custom_field_id']]['0'], "In line " . __LINE__);
    $this->assertEquals('value 1', $result['values'][$this->ids['single']['custom_field_id']]['latest'], "In line " . __LINE__);
    $this->assertEquals('value 1', $resultformatted['values']['mySingleField']['latest'], "In line " . __LINE__);
  }
  /*
   public function testDeleteCustomValue () {
        $entity = civicrm_api('custom_value','get',$this->params);
        $result = civicrm_api('custom_value','delete',array('version' =>3,'id' => $entity['id']));
        $this->documentMe($this->params,$result,__FUNCTION__,__FILE__);
        $this->assertAPISuccess($result, 'In line ' . __LINE__ );
        $checkDeleted = civicrm_api('survey','get',array('version' =>3,));
        $this->assertEquals( 0, $checkDeleted['count'], 'In line ' . __LINE__ );

    }

   public function testGetCustomValueChainDelete () {
        $description = "demonstrates get + delete in the same call";
        $subfile     = 'ChainedGetDelete';
        $params      = array(
          'version' =>3,
                        'title'   => "survey title",
                        'api.survey.delete' => 1);
        $result = civicrm_api('survey','create',$this->params);
        $result = civicrm_api('survey','get',$params );
        $this->documentMe($params,$result,__FUNCTION__,__FILE__,$description,$subfile);
        $this->assertAPISuccess($result, 'In line ' . __LINE__ );
        $this->assertEquals( 0,civicrm_api('survey','getcount',array('version' => 3)), 'In line ' . __LINE__ );

    }
    */
}

