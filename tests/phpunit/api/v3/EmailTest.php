<?php
/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.5                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2014                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_EmailTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_contactID;
  protected $_locationType;
  protected $_entity;
  protected $_params;

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
    $get = $this->callAPISuccess('email', 'get', array(
      'location_type_id' => $this->_locationType->id,
    ));
    $this->assertEquals(0, $get['count'], 'Contact not successfully deleted In line ' . __LINE__);

    $result = $this->callAPIAndDocument('email', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['id'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $delresult = $this->callAPISuccess('email', 'delete', array('id' => $result['id']));
  }
  /*
   * If a new email is set to is_primary the prev should no longer be
   *
   * If is_primary is not set then it should become is_primary is no others exist
   */



  public function testCreateEmailPrimaryHandlingChangeToPrimary() {
    $params = $this->_params;
    unset($params['is_primary']);
    $email1 = $this->callAPISuccess('email', 'create', $params);
    //now we check & make sure it has been set to primary
    $expected = 1;
    $check = $this->callAPISuccess('email', 'getcount', array(
      'is_primary' => 1,
      'id' => $email1['id'],
      ),
      $expected
     );
  }

  public function testCreateEmailPrimaryHandlingChangeExisting() {
    $email1 = $this->callAPISuccess('email', 'create', $this->_params);
    $email2 = $this->callAPISuccess('email', 'create', $this->_params);
    $check = $this->callAPISuccess('email', 'getcount', array(
        'is_primary' => 1,
        'contact_id' => $this->_contactID,
      ));
    $this->assertEquals(1, $check);
  }

  public function testCreateEmailWithoutEmail() {
    $result = $this->callAPIFailure('Email', 'Create', array('contact_id' => 4));
    $this->assertContains('missing', $result['error_message'], 'In line ' . __LINE__);
    $this->assertContains('email', $result['error_message'], 'In line ' . __LINE__);
  }

  public function testGetEmail() {
    $result = $this->callAPISuccess('email', 'create', $this->_params);
    $get = $this->callAPISuccess('email', 'create', $this->_params);
    $this->assertEquals($get['count'], 1);
    $get = $this->callAPISuccess('email', 'create', $this->_params + array('debug' => 1));
    $this->assertEquals($get['count'], 1);
    $get = $this->callAPISuccess('email', 'create', $this->_params + array('debug' => 1, 'action' => 'get'));
    $this->assertEquals($get['count'], 1);
    $delresult = $this->callAPISuccess('email', 'delete', array('id' => $result['id']));
  }
  public function testDeleteEmail() {
    $params = array(
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
      'email' => 'api@a-team.com',
      'is_primary' => 1,

      //TODO email_type_id
    );
    //check there are no emails to start with
    $get = $this->callAPISuccess('email', 'get', array(
     'location_type_id' => $this->_locationType->id,
    ));
    $this->assertEquals(0, $get['count'], 'email already exists ' . __LINE__);

    //create one
    $create = $this->callAPISuccess('email', 'create', $params);

    $result = $this->callAPIAndDocument('email', 'delete', array('id' => $create['id'],), __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $get = $this->callAPISuccess('email', 'get', array(
      'location_type_id' => $this->_locationType->id,
    ));
    $this->assertEquals(0, $get['count'], 'Contact not successfully deleted In line ' . __LINE__);
  }

  public function testReplaceEmail() {
    // check there are no emails to start with
    $get = $this->callAPISuccess('email', 'get', array(
        'contact_id' => $this->_contactID,
      ));
    $this->assertEquals(0, $get['count'], 'email already exists ' . __LINE__);

    // initialize email list with three emails at loc #1 and two emails at loc #2
    $replace1Params = array(
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
    $replace1 = $this->callAPIAndDocument('email', 'replace', $replace1Params, __FUNCTION__, __FILE__);
    $this->assertEquals(5, $replace1['count'], 'In line ' . __LINE__);

    // check emails at location #1 or #2
    $get = $this->callAPISuccess('email', 'get', array(
        'contact_id' => $this->_contactID,
      ));
    $this->assertEquals(5, $get['count'], 'Incorrect email count at ' . __LINE__);

    // replace the subset of emails in location #1, but preserve location #2
    $replace2Params = array(
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
      'values' => array(
        array(
          'email' => '1-4@example.com',
          'is_primary' => 1,
        ),
      ),
    );
    $replace2 = $this->callAPISuccess('email', 'replace', $replace2Params);
    $this->assertEquals(1, $replace2['count'], 'In line ' . __LINE__);

    // check emails at location #1 -- all three replaced by one
    $get = $this->callAPISuccess('email', 'get', array(
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
    ));
    $this->assertEquals(1, $get['count'], 'Incorrect email count at ' . __LINE__);

    // check emails at location #2 -- preserve the original two
    $get = $this->callAPISuccess('email', 'get', array(
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType2->id,
    ));

    $this->assertEquals(2, $get['count'], 'Incorrect email count at ' . __LINE__);

    // replace the set of emails with an empty set
    $replace3Params = array(
      'contact_id' => $this->_contactID,
      'values' => array(),
    );
    $replace3 = $this->callAPISuccess('email', 'replace', $replace3Params);
    $this->assertEquals(0, $replace3['count'], 'In line ' . __LINE__);

    // check emails
    $get = $this->callAPISuccess('email', 'get', array(

        'contact_id' => $this->_contactID,
      ));
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'Incorrect email count at ' . __LINE__);
  }

  public function testReplaceEmailsInChain() {
    // check there are no emails to start with
    $get = $this->callAPISuccess('email', 'get', array(

        'contact_id' => $this->_contactID,
      ));
    $this->assertAPISuccess($get, 'In line ' . __LINE__);
    $this->assertEquals(0, $get['count'], 'email already exists ' . __LINE__);
    $description = "example demonstrates use of Replace in a nested API call";
    $subfile = "NestedReplaceEmail";
    // initialize email list with three emails at loc #1 and two emails at loc #2
    $getReplace1Params = array(

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
    $getReplace1 = $this->callAPIAndDocument('contact', 'get', $getReplace1Params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(5, $getReplace1['values'][$this->_contactID]['api.email.replace']['count'], 'In line ' . __LINE__);

    // check emails at location #1 or #2
    $get = $this->callAPISuccess('email', 'get', array(
        'contact_id' => $this->_contactID,
      ));
    $this->assertEquals(5, $get['count'], 'Incorrect email count at ' . __LINE__);

    // replace the subset of emails in location #1, but preserve location #2
    $getReplace2Params = array(
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
    $getReplace2 = $this->callAPISuccess('contact', 'get', $getReplace2Params);
    $this->assertEquals(0, $getReplace2['values'][$this->_contactID]['api.email.replace']['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(1, $getReplace2['values'][$this->_contactID]['api.email.replace']['count'], 'In line ' . __LINE__);

    // check emails at location #1 -- all three replaced by one
    $get = $this->callAPISuccess('email', 'get', array(
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
    ));

    $this->assertEquals(1, $get['count'], 'Incorrect email count at ' . __LINE__);

    // check emails at location #2 -- preserve the original two
    $get = $this->callAPISuccess('email', 'get', array(
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType2->id,
    ));
    $this->assertEquals(2, $get['count'], 'Incorrect email count at ' . __LINE__);
  }

  public function testReplaceEmailWithId() {
    // check there are no emails to start with
    $get = $this->callAPISuccess('email', 'get', array(
        'contact_id' => $this->_contactID,
      ));
    $this->assertEquals(0, $get['count'], 'email already exists ' . __LINE__);

    // initialize email address
    $replace1Params = array(
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
    $replace1 = $this->callAPISuccess('email', 'replace', $replace1Params);
    $this->assertEquals(1, $replace1['count'], 'In line ' . __LINE__);

    $keys = array_keys($replace1['values']);
    $emailID = array_shift($keys);

    // update the email address, but preserve any other fields
    $replace2Params = array(
      'contact_id' => $this->_contactID,
      'values' => array(
        array(
          'id' => $emailID,
          'email' => '1-2@example.com',
        ),
      ),
    );
    $replace2 = $this->callAPISuccess('email', 'replace', $replace2Params);
    $this->assertEquals(1, $replace2['count'], 'In line ' . __LINE__);

    // ensure the 'email' was updated while other fields were preserved
    $get = $this->callAPISuccess('email', 'get', array(
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
    ));

    $this->assertEquals(1, $get['count'], 'Incorrect email count at ' . __LINE__);
    $this->assertEquals(1, $get['values'][$emailID]['is_primary'], 'In line ' . __LINE__);
    $this->assertEquals(1, $get['values'][$emailID]['on_hold'], 'In line ' . __LINE__);
    $this->assertEquals('1-2@example.com', $get['values'][$emailID]['email'], 'In line ' . __LINE__);
  }
}

