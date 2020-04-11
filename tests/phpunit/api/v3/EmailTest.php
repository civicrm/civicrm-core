<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Class api_v3_EmailTest
 *
 * @group headless
 */
class api_v3_EmailTest extends CiviUnitTestCase {
  protected $_contactID;
  protected $_locationType;
  protected $locationType2;
  protected $_entity;
  protected $_params;

  public function setUp() {
    $this->_entity = 'Email';
    parent::setUp();
    $this->useTransaction(TRUE);

    $this->_contactID = $this->organizationCreate(NULL);
    $this->_locationType = $this->locationTypeCreate(NULL);
    $this->locationType2 = $this->locationTypeCreate([
      'name' => 'New Location Type 2',
      'vcard_name' => 'New Location Type 2',
      'description' => 'Another Location Type',
      'is_active' => 1,
    ]);
    $this->_params = [
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
      'email' => 'api@a-team.com',
      'is_primary' => 1,

      //TODO email_type_id
    ];
  }

  /**
   * Test create email.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testCreateEmail($version) {
    $this->_apiversion = $version;
    $params = $this->_params;
    //check there are no emails to start with
    $get = $this->callAPISuccess('email', 'get', [
      'location_type_id' => $this->_locationType->id,
    ]);
    $this->assertEquals(0, $get['count'], 'Contact not successfully deleted.');

    $result = $this->callAPIAndDocument('email', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['id']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->callAPISuccess('email', 'delete', ['id' => $result['id']]);
  }

  /**
   * If no location is specified when creating a new email, it should default to
   * the LocationType default
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testCreateEmailDefaultLocation($version) {
    $this->_apiversion = $version;
    $params = $this->_params;
    unset($params['location_type_id']);
    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(CRM_Core_BAO_LocationType::getDefault()->id, $result['values'][$result['id']]['location_type_id']);
    $this->callAPISuccess($this->_entity, 'delete', ['id' => $result['id']]);
  }

  /**
   * If a new email is set to is_primary the prev should no longer be.
   *
   * If is_primary is not set then it should become is_primary is no others exist
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testCreateEmailPrimaryHandlingChangeToPrimary($version) {
    $this->_apiversion = $version;
    $params = $this->_params;
    unset($params['is_primary']);
    $email1 = $this->callAPISuccess('email', 'create', $params);
    //now we check & make sure it has been set to primary
    $expected = 1;
    $this->callAPISuccess('email', 'getcount', [
      'is_primary' => 1,
      'id' => $email1['id'],
    ],
      $expected
    );
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testCreateEmailPrimaryHandlingChangeExisting($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess('email', 'create', $this->_params);
    $this->callAPISuccess('email', 'create', $this->_params);
    $check = $this->callAPISuccess('email', 'getcount', [
      'is_primary' => 1,
      'contact_id' => $this->_contactID,
    ]);
    $this->assertEquals(1, $check);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateEmailWithoutEmail($version) {
    $this->_apiversion = $version;
    $result = $this->callAPIFailure('Email', 'Create', ['contact_id' => 4]);
    $this->assertContains('missing', $result['error_message']);
    $this->assertContains('email', $result['error_message']);
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testGetEmail($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess('email', 'create', $this->_params);
    $get = $this->callAPISuccess('email', 'create', $this->_params);
    $this->assertEquals($get['count'], 1);
    $get = $this->callAPISuccess('email', 'create', $this->_params + ['debug' => 1]);
    $this->assertEquals($get['count'], 1);
    $get = $this->callAPISuccess('email', 'create', $this->_params + ['debug' => 1, 'action' => 'get']);
    $this->assertEquals($get['count'], 1);
    $this->callAPISuccess('email', 'delete', ['id' => $result['id']]);
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testDeleteEmail($version) {
    $this->_apiversion = $version;
    $params = [
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
      'email' => 'api@a-team.com',
      'is_primary' => 1,

      //TODO email_type_id
    ];
    //check there are no emails to start with
    $get = $this->callAPISuccess('email', 'get', [
      'location_type_id' => $this->_locationType->id,
    ]);
    $this->assertEquals(0, $get['count'], 'email already exists');

    //create one
    $create = $this->callAPISuccess('email', 'create', $params);

    $result = $this->callAPIAndDocument('email', 'delete', ['id' => $create['id']], __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $get = $this->callAPISuccess('email', 'get', [
      'location_type_id' => $this->_locationType->id,
    ]);
    $this->assertEquals(0, $get['count'], 'Contact not successfully deleted');
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testReplaceEmail($version) {
    $this->_apiversion = $version;
    // check there are no emails to start with
    $get = $this->callAPISuccess('email', 'get', [
      'contact_id' => $this->_contactID,
    ]);
    $this->assertEquals(0, $get['count'], 'email already exists');

    // initialize email list with three emails at loc #1 and two emails at loc #2
    $replace1Params = [
      'contact_id' => $this->_contactID,
      'values' => [
        [
          'location_type_id' => $this->_locationType->id,
          'email' => '1-1@example.com',
          'is_primary' => 1,
        ],
        [
          'location_type_id' => $this->_locationType->id,
          'email' => '1-2@example.com',
          'is_primary' => 0,
        ],
        [
          'location_type_id' => $this->_locationType->id,
          'email' => '1-3@example.com',
          'is_primary' => 0,
        ],
        [
          'location_type_id' => $this->locationType2->id,
          'email' => '2-1@example.com',
          'is_primary' => 0,
        ],
        [
          'location_type_id' => $this->locationType2->id,
          'email' => '2-2@example.com',
          'is_primary' => 0,
        ],
      ],
    ];
    $replace1 = $this->callAPIAndDocument('email', 'replace', $replace1Params, __FUNCTION__, __FILE__);
    $this->assertEquals(5, $replace1['count']);

    // check emails at location #1 or #2
    $get = $this->callAPISuccess('email', 'get', [
      'contact_id' => $this->_contactID,
    ]);
    $this->assertEquals(5, $get['count'], 'Incorrect email count');

    // replace the subset of emails in location #1, but preserve location #2
    $replace2Params = [
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
      'values' => [
        [
          'email' => '1-4@example.com',
          'is_primary' => 1,
        ],
      ],
    ];
    $replace2 = $this->callAPISuccess('email', 'replace', $replace2Params);
    $this->assertEquals(1, $replace2['count']);

    // check emails at location #1 -- all three replaced by one
    $get = $this->callAPISuccess('email', 'get', [
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
    ]);
    $this->assertEquals(1, $get['count'], 'Incorrect email count');

    // check emails at location #2 -- preserve the original two
    $get = $this->callAPISuccess('email', 'get', [
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->locationType2->id,
    ]);

    $this->assertEquals(2, $get['count'], 'Incorrect email count');

    // replace the set of emails with an empty set
    $replace3Params = [
      'contact_id' => $this->_contactID,
      'values' => [],
    ];
    $replace3 = $this->callAPISuccess('email', 'replace', $replace3Params);
    $this->assertEquals(0, $replace3['count']);

    // check emails
    $get = $this->callAPISuccess('email', 'get', [

      'contact_id' => $this->_contactID,
    ]);
    $this->assertAPISuccess($get);
    $this->assertEquals(0, $get['count'], 'Incorrect email count');
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testReplaceEmailsInChain($version) {
    $this->_apiversion = $version;
    // check there are no emails to start with
    $get = $this->callAPISuccess('email', 'get', [

      'contact_id' => $this->_contactID,
    ]);
    $this->assertAPISuccess($get);
    $this->assertEquals(0, $get['count'], 'email already exists');
    $description = 'Demonstrates use of Replace in a nested API call.';
    $subfile = 'NestedReplaceEmail';
    // initialize email list with three emails at loc #1 and two emails at loc #2
    $getReplace1Params = [

      'id' => $this->_contactID,
      'api.email.replace' => [
        'values' => [
          [
            'location_type_id' => $this->_locationType->id,
            'email' => '1-1@example.com',
            'is_primary' => 1,
          ],
          [
            'location_type_id' => $this->_locationType->id,
            'email' => '1-2@example.com',
            'is_primary' => 0,
          ],
          [
            'location_type_id' => $this->_locationType->id,
            'email' => '1-3@example.com',
            'is_primary' => 0,
          ],
          [
            'location_type_id' => $this->locationType2->id,
            'email' => '2-1@example.com',
            'is_primary' => 0,
          ],
          [
            'location_type_id' => $this->locationType2->id,
            'email' => '2-2@example.com',
            'is_primary' => 0,
          ],
        ],
      ],
    ];
    $getReplace1 = $this->callAPIAndDocument('contact', 'get', $getReplace1Params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(5, $getReplace1['values'][$this->_contactID]['api.email.replace']['count']);

    // check emails at location #1 or #2
    $get = $this->callAPISuccess('email', 'get', [
      'contact_id' => $this->_contactID,
    ]);
    $this->assertEquals(5, $get['count'], 'Incorrect email count');

    // replace the subset of emails in location #1, but preserve location #2
    $getReplace2Params = [
      'id' => $this->_contactID,
      'api.email.replace' => [
        'location_type_id' => $this->_locationType->id,
        'values' => [
          [
            'email' => '1-4@example.com',
            'is_primary' => 1,
          ],
        ],
      ],
    ];
    $getReplace2 = $this->callAPISuccess('contact', 'get', $getReplace2Params);
    $this->assertEquals(0, $getReplace2['values'][$this->_contactID]['api.email.replace']['is_error']);
    $this->assertEquals(1, $getReplace2['values'][$this->_contactID]['api.email.replace']['count']);

    // check emails at location #1 -- all three replaced by one
    $get = $this->callAPISuccess('email', 'get', [
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
    ]);

    $this->assertEquals(1, $get['count'], 'Incorrect email count');

    // check emails at location #2 -- preserve the original two
    $get = $this->callAPISuccess('email', 'get', [
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->locationType2->id,
    ]);
    $this->assertEquals(2, $get['count'], 'Incorrect email count');
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testReplaceEmailWithId($version) {
    $this->_apiversion = $version;
    // check there are no emails to start with
    $get = $this->callAPISuccess('email', 'get', [
      'contact_id' => $this->_contactID,
    ]);
    $this->assertEquals(0, $get['count'], 'email already exists ' . __LINE__);

    // initialize email address
    $replace1Params = [
      'contact_id' => $this->_contactID,
      'values' => [
        [
          'location_type_id' => $this->_locationType->id,
          'email' => '1-1@example.com',
          'is_primary' => 1,
          'on_hold' => 1,
        ],
      ],
    ];
    $replace1 = $this->callAPISuccess('email', 'replace', $replace1Params);
    $this->assertEquals(1, $replace1['count']);

    $keys = array_keys($replace1['values']);
    $emailID = array_shift($keys);

    // update the email address, but preserve any other fields
    $replace2Params = [
      'contact_id' => $this->_contactID,
      'values' => [
        [
          'id' => $emailID,
          'email' => '1-2@example.com',
        ],
      ],
    ];
    $replace2 = $this->callAPISuccess('email', 'replace', $replace2Params);
    $this->assertEquals(1, $replace2['count']);

    // ensure the 'email' was updated while other fields were preserved
    $get = $this->callAPISuccess('email', 'get', [
      'contact_id' => $this->_contactID,
      'location_type_id' => $this->_locationType->id,
    ]);

    $this->assertEquals(1, $get['count'], 'Incorrect email count at ' . __LINE__);
    $this->assertEquals(1, $get['values'][$emailID]['is_primary']);
    $this->assertEquals(1, $get['values'][$emailID]['on_hold']);
    $this->assertEquals('1-2@example.com', $get['values'][$emailID]['email']);
  }

  /**
   * Test updates affecting on hold emails.
   *
   * @throws \CRM_Core_Exception
   */
  public function testEmailOnHold() {
    $params = [
      'contact_id' => $this->_contactID,
      'email' => 'api@a-team.com',
      'on_hold' => '2',
    ];
    $result = $this->callAPIAndDocument('email', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['id']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->assertEquals(2, $result['values'][$result['id']]['on_hold']);
    $this->assertEquals(date('Y-m-d H:i'), date('Y-m-d H:i', strtotime($result['values'][$result['id']]['hold_date'])));

    // set on_hold is '0'
    // if isMultipleBulkMail is active, the value in On-hold select is string
    $params_change = [
      'id' => $result['id'],
      'contact_id' => $this->_contactID,
      'email' => 'api@a-team.com',
      'is_primary' => 1,
      'on_hold' => '0',
    ];
    $result_change = $this->callAPISuccess('email', 'create', $params_change + ['action' => 'get']);
    $this->assertEquals(1, $result_change['count']);
    $this->assertEquals($result['id'], $result_change['id']);
    $this->assertEmpty($result_change['values'][$result_change['id']]['on_hold']);
    $this->assertEquals(date('Y-m-d H:i'), date('Y-m-d H:i', strtotime($result_change['values'][$result_change['id']]['reset_date'])));
    $this->assertEmpty($result_change['values'][$result_change['id']]['hold_date']);

    $this->callAPISuccess('email', 'delete', ['id' => $result['id']]);
  }

  /**
   * Test setting a bulk email unsets others on the contact.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSetBulkEmail() {
    $individualID = $this->individualCreate([]);
    $email = $this->callAPISuccessGetSingle('Email', ['contact_id' => $individualID]);
    $this->assertEquals(0, $email['is_bulkmail']);
    $this->callAPISuccess('Email', 'create', ['id' => $email['id'], 'is_bulkmail' => 1]);
    $email = $this->callAPISuccessGetSingle('Email', ['contact_id' => $individualID]);
    $this->assertEquals(1, $email['is_bulkmail']);
    $email2 = $this->callAPISuccess('Email', 'create', ['contact_id' => $individualID, 'email' => 'mail@Example.com', 'is_bulkmail' => 1]);
    $emails = $this->callAPISuccess('Email', 'get', ['contact_id' => $individualID])['values'];
    $this->assertEquals(0, $emails[$email['id']]['is_bulkmail']);
    $this->assertEquals(1, $emails[$email2['id']]['is_bulkmail']);
  }

  /**
   * Test getlist.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetlist() {
    $name = 'Scarabée';
    $emailMatchContactID = $this->individualCreate(['last_name' => $name, 'email' => 'bob@bob.com']);
    $emailMatchEmailID = $this->callAPISuccessGetValue('Email', ['return' => 'id', 'contact_id' => $emailMatchContactID]);
    $this->individualCreate(['last_name' => $name, 'email' => 'bob@bob.com', 'is_deceased' => 1]);
    $this->individualCreate(['last_name' => $name, 'email' => 'bob@bob.com', 'is_deleted' => 1]);
    $this->individualCreate(['last_name' => $name, 'api.email.create' => ['email' => 'bob@bob.com', 'on_hold' => 1]]);
    $this->individualCreate(['last_name' => $name, 'do_not_email' => 1, 'api.email.create' => ['email' => 'bob@bob.com']]);
    $nameMatchContactID = $this->individualCreate(['last_name' => 'bob', 'email' => 'blah@example.com']);
    $nameMatchEmailID = $this->callAPISuccessGetValue('Email', ['return' => 'id', 'contact_id' => $nameMatchContactID]);
    // We should get only the active live email-able contact.
    $result = $this->callAPISuccess('Email', 'getlist', ['input' => 'bob'])['values'];
    $this->assertCount(2, $result);
    $this->assertEquals($nameMatchEmailID, $result[0]['id']);
    $this->assertEquals($emailMatchEmailID, $result[1]['id']);
  }

}
