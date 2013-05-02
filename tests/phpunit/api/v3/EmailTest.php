<?php
// $Id$


require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_EmailTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_contactID;
  protected $_locationType;
  protected $_entity;
  protected $_params;
  public $_eNoticeCompliant = TRUE;
  function setUp() {
    $this->_apiversion = 3;
    $this->_entity = 'Email';
    parent::setUp();
    $this->_contactID = $this->organizationCreate(NULL);
    $this->_locationType = $this->locationTypeCreate(NULL);
    $this->_locationType2 = $this->locationTypeCreate(array(
        'name' => 'New Location Type 2',
        'vcard_name' => 'New Location Type 2',
        'description' => 'Another Location Type',
        'is_active' => 1,
      ));
    $this->_params = array(
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
      'email' => 'api@a-team.com',
      'is_primary' => 1,
      'version' => $this->_apiversion,
      //TODO email_type_id
    );
  }

  function tearDown() {
    $this->contactDelete($this->_contactID);
    $this->locationTypeDelete($this->_locationType->id);
    $this->locationTypeDelete($this->_locationType2->id);
  }
  public function testCreateEmail() {
    $params = $this->_params;
    //check there are no emails to start with
    $get = civicrm_api('email', 'get', array(
      'version' => 3,
        'location_type_id' => $this->_locationType->id,
      ));
    $this->assertEquals(0, $get['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'Contact not successfully deleted In line ' . __LINE__);
    $result = civicrm_api('email', 'create', $params);

    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertApiSuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['id'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $delresult = civicrm_api('email', 'delete', array('id' => $result['id'], 'version' => 3));
    $this->assertEquals(0, $delresult['is_error'], 'In line ' . __LINE__);
  }
  /*
   * If a new email is set to is_primary the prev should no longer be
   *
   * If is_primary is not set then it should become is_primary is no others exist
   */



  public function testCreateEmailPrimaryHandlingChangeToPrimary() {
    $params = $this->_params;
    unset($params['is_primary']);
    $email1 = civicrm_api('email', 'create', $params);
    $this->assertApiSuccess($email1, 'In line ' . __LINE__);
    //now we check & make sure it has been set to primary
    $check = civicrm_api('email', 'getcount', array(
        'version' => 3,
        'is_primary' => 1,
        'id' => $email1['id'],
      ));
    $this->assertEquals(1, $check);
  }
  public function testCreateEmailPrimaryHandlingChangeExisting() {
    $email1 = civicrm_api('email', 'create', $this->_params);
    $this->assertApiSuccess($email1, 'In line ' . __LINE__);
    $email2 = civicrm_api('email', 'create', $this->_params);
    $this->assertApiSuccess($email2, 'In line ' . __LINE__);
    $check = civicrm_api('email', 'getcount', array(
        'version' => 3,
        'is_primary' => 1,
        'contact_id' => $this->_contactID,
      ));
    $this->assertEquals(1, $check);
  }

  public function testCreateEmailWithoutEmail() {
    $result = civicrm_api('Email', 'Create', array('contact_id' => 4, 'version' => 3));
    $this->assertEquals(1, $result['is_error'], 'In line ' . __LINE__);
    $this->assertContains('missing', $result['error_message'], 'In line ' . __LINE__);
    $this->assertContains('email', $result['error_message'], 'In line ' . __LINE__);
  }

  public function testGetEmail() {
    $result = civicrm_api('email', 'create', $this->_params);
    $this->assertAPISuccess($result, 'create email in line ' . __LINE__);
    $get = civicrm_api('email', 'create', $this->_params);
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals($get['count'], 1);
    $get = civicrm_api('email', 'create', $this->_params + array('debug' => 1));
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals($get['count'], 1);
    $get = civicrm_api('email', 'create', $this->_params + array('debug' => 1, 'action' => 'get'));
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals($get['count'], 1);
    $delresult = civicrm_api('email', 'delete', array('id' => $result['id'], 'version' => 3));
    $this->assertAPISuccess($delresult, 'In line ' . __LINE__);
  }
  public function testDeleteEmail() {
    $params = array(
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
      'email' => 'api@a-team.com',
      'is_primary' => 1,
      'version' => $this->_apiversion,
      //TODO email_type_id
    );
    //check there are no emails to start with
    $get = civicrm_api('email', 'get', array(
      'version' => 3,
        'location_type_id' => $this->_locationType->id,
      ));
    $this->assertEquals(0, $get['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'email already exists ' . __LINE__);

    //create one
    $create = civicrm_api('email', 'create', $params);

    $this->assertEquals(0, $create['is_error'], 'In line ' . __LINE__);

    $result = civicrm_api('email', 'delete', array('id' => $create['id'], 'version' => 3));
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $get = civicrm_api('email', 'get', array(
      'version' => 3,
        'location_type_id' => $this->_locationType->id,
      ));
    $this->assertEquals(0, $get['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'Contact not successfully deleted In line ' . __LINE__);
  }

  public function testReplaceEmail() {
    // check there are no emails to start with
    $get = civicrm_api('email', 'get', array(
        'version' => $this->_apiversion,
        'contact_id' => $this->_contactID,
      ));
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'email already exists ' . __LINE__);

    // initialize email list with three emails at loc #1 and two emails at loc #2
    $replace1Params = array(
      'version' => $this->_apiversion,
      'contact_id' => $this->_contactID,
      'values' => array(
        array(
          'location_type_id' => $this->_locationType->id,
          'email' => '1-1@example.com',
          'is_primary' => 1,
        ),
        array(
          'location_type_id' => $this->_locationType->id,
          'email' => '1-2@example.com',
          'is_primary' => 0,
        ),
        array(
          'location_type_id' => $this->_locationType->id,
          'email' => '1-3@example.com',
          'is_primary' => 0,
        ),
        array(
          'location_type_id' => $this->_locationType2->id,
          'email' => '2-1@example.com',
          'is_primary' => 0,
        ),
        array(
          'location_type_id' => $this->_locationType2->id,
          'email' => '2-2@example.com',
          'is_primary' => 0,
        ),
      ),
    );
    $replace1 = civicrm_api('email', 'replace', $replace1Params);
    $this->documentMe($replace1Params, $replace1, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($replace1, 'In line ' . __LINE__);
    $this->assertEquals(5, $replace1['count'], 'In line ' . __LINE__);

    // check emails at location #1 or #2
    $get = civicrm_api('email', 'get', array(
        'version' => $this->_apiversion,
        'contact_id' => $this->_contactID,
      ));
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals(5, $get['count'], 'Incorrect email count at ' . __LINE__);

    // replace the subset of emails in location #1, but preserve location #2
    $replace2Params = array(
      'version' => $this->_apiversion,
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
      'values' => array(
        array(
          'email' => '1-4@example.com',
          'is_primary' => 1,
        ),
      ),
    );
    $replace2 = civicrm_api('email', 'replace', $replace2Params);
    // $this->documentMe($replace2Params, $replace2, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($replace2, 'In line ' . __LINE__);
    $this->assertEquals(1, $replace2['count'], 'In line ' . __LINE__);

    // check emails at location #1 -- all three replaced by one
    $get = civicrm_api('email', 'get', array(
        'version' => $this->_apiversion,
        'contact_id' => $this->_contactID,
        'location_type_id' => $this->_locationType->id,
      ));
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals(1, $get['count'], 'Incorrect email count at ' . __LINE__);

    // check emails at location #2 -- preserve the original two
    $get = civicrm_api('email', 'get', array(
        'version' => $this->_apiversion,
        'contact_id' => $this->_contactID,
        'location_type_id' => $this->_locationType2->id,
      ));
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals(2, $get['count'], 'Incorrect email count at ' . __LINE__);

    // replace the set of emails with an empty set
    $replace3Params = array(
      'version' => $this->_apiversion,
      'contact_id' => $this->_contactID,
      'values' => array(),
    );
    $replace3 = civicrm_api('email', 'replace', $replace3Params);
    // $this->documentMe($replace3Params, $replace3, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $replace3['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $replace3['count'], 'In line ' . __LINE__);

    // check emails
    $get = civicrm_api('email', 'get', array(
        'version' => $this->_apiversion,
        'contact_id' => $this->_contactID,
      ));
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'Incorrect email count at ' . __LINE__);
  }

  public function testReplaceEmailsInChain() {
    // check there are no emails to start with
    $get = civicrm_api('email', 'get', array(
        'version' => $this->_apiversion,
        'contact_id' => $this->_contactID,
      ));
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'email already exists ' . __LINE__);
    $description = "example demonstrates use of Replace in a nested API call";
    $subfile = "NestedReplaceEmail";
    // initialize email list with three emails at loc #1 and two emails at loc #2
    $getReplace1Params = array(
      'version' => $this->_apiversion,
      'id' => $this->_contactID,
      'api.email.replace' => array(
        'values' => array(
          array(
            'location_type_id' => $this->_locationType->id,
            'email' => '1-1@example.com',
            'is_primary' => 1,
          ),
          array(
            'location_type_id' => $this->_locationType->id,
            'email' => '1-2@example.com',
            'is_primary' => 0,
          ),
          array(
            'location_type_id' => $this->_locationType->id,
            'email' => '1-3@example.com',
            'is_primary' => 0,
          ),
          array(
            'location_type_id' => $this->_locationType2->id,
            'email' => '2-1@example.com',
            'is_primary' => 0,
          ),
          array(
            'location_type_id' => $this->_locationType2->id,
            'email' => '2-2@example.com',
            'is_primary' => 0,
          ),
        ),
      ),
    );
    $getReplace1 = civicrm_api('contact', 'get', $getReplace1Params);

    $this->documentMe($getReplace1Params, $getReplace1, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertAPISuccess($getReplace1['values'][$this->_contactID]['api.email.replace'], 'In line ' . __LINE__);
    $this->assertEquals(5, $getReplace1['values'][$this->_contactID]['api.email.replace']['count'], 'In line ' . __LINE__);

    // check emails at location #1 or #2
    $get = civicrm_api('email', 'get', array(
        'version' => $this->_apiversion,
        'contact_id' => $this->_contactID,
      ));
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals(5, $get['count'], 'Incorrect email count at ' . __LINE__);

    // replace the subset of emails in location #1, but preserve location #2
    $getReplace2Params = array(
      'version' => $this->_apiversion,
      'id' => $this->_contactID,
      'api.email.replace' => array(
        'location_type_id' => $this->_locationType->id,
        'values' => array(
          array(
            'email' => '1-4@example.com',
            'is_primary' => 1,
          ),
        ),
      ),
    );
    $getReplace2 = civicrm_api('contact', 'get', $getReplace2Params);
    // $this->documentMe($getReplace2Params, $getReplace2, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $getReplace2['values'][$this->_contactID]['api.email.replace']['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(1, $getReplace2['values'][$this->_contactID]['api.email.replace']['count'], 'In line ' . __LINE__);

    // check emails at location #1 -- all three replaced by one
    $get = civicrm_api('email', 'get', array(
        'version' => $this->_apiversion,
        'contact_id' => $this->_contactID,
        'location_type_id' => $this->_locationType->id,
      ));

    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals(1, $get['count'], 'Incorrect email count at ' . __LINE__);

    // check emails at location #2 -- preserve the original two
    $get = civicrm_api('email', 'get', array(
        'version' => $this->_apiversion,
        'contact_id' => $this->_contactID,
        'location_type_id' => $this->_locationType2->id,
      ));
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals(2, $get['count'], 'Incorrect email count at ' . __LINE__);
  }

  public function testReplaceEmailWithId() {
    // check there are no emails to start with
    $get = civicrm_api('email', 'get', array(
        'version' => $this->_apiversion,
        'contact_id' => $this->_contactID,
      ));
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'email already exists ' . __LINE__);

    // initialize email address
    $replace1Params = array(
      'version' => $this->_apiversion,
      'contact_id' => $this->_contactID,
      'values' => array(
        array(
          'location_type_id' => $this->_locationType->id,
          'email' => '1-1@example.com',
          'is_primary' => 1,
          'on_hold' => 1,
        ),
      ),
    );
    $replace1 = civicrm_api('email', 'replace', $replace1Params);
    // $this->documentMe($replace1Params, $replace1, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($replace1, 'In line ' . __LINE__);
    $this->assertEquals(1, $replace1['count'], 'In line ' . __LINE__);

    $keys = array_keys($replace1['values']);
    $emailID = array_shift($keys);

    // update the email address, but preserve any other fields
    $replace2Params = array(
      'version' => $this->_apiversion,
      'contact_id' => $this->_contactID,
      'values' => array(
        array(
          'id' => $emailID,
          'email' => '1-2@example.com',
        ),
      ),
    );
    $replace2 = civicrm_api('email', 'replace', $replace2Params);
    // $this->documentMe($replace2Params, $replace2, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($replace2, 'In line ' . __LINE__);
    $this->assertEquals(1, $replace2['count'], 'In line ' . __LINE__);

    // ensure the 'email' was updated while other fields were preserved
    $get = civicrm_api('email', 'get', array(
        'version' => $this->_apiversion,
        'contact_id' => $this->_contactID,
        'location_type_id' => $this->_locationType->id,
      ));
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals(1, $get['count'], 'Incorrect email count at ' . __LINE__);
    $this->assertEquals(1, $get['values'][$emailID]['is_primary'], 'In line ' . __LINE__);
    $this->assertEquals(1, $get['values'][$emailID]['on_hold'], 'In line ' . __LINE__);
    $this->assertEquals('1-2@example.com', $get['values'][$emailID]['email'], 'In line ' . __LINE__);
  }
}

