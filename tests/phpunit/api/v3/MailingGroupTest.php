<?php
/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2013                                |
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

/**
 *  Test APIv3 civicrm_mailing_group_* functions
 *
 *  @package   CiviCRM
 */
class api_v3_MailingGroupTest extends CiviUnitTestCase {
  protected $_groupID;
  protected $_email;
  protected $_apiversion;

  function get_info() {
    return array(
      'name' => 'Mailer Group',
      'description' => 'Test all Mailer Group methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    parent::setUp();
    $this->_apiversion = 3;
    $this->_groupID    = $this->groupCreate(NULL);
    $this->_email      = 'test@test.test';
  }

  function tearDown() {
    $this->groupDelete($this->_groupID);
  }

  //---------- civicrm_mailing_event_subscribe methods ---------

  /**
   * Test civicrm_mailing_group_event_subscribe with wrong params.
   */
  public function testMailerGroupSubscribeWrongParams() {
    $params = array(
      'email' => $this->_email,
      'group_id' => 'Wrong Group ID',
      'contact_id' => '2121',
      'version' => $this->_apiversion,
      'time_stamp' => '20111111010101',
      'hash' => 'sasa',
    );
    $result = $this->callAPIFailure('mailing_event_subscribe', 'create', $params);
    if ($result['error_message'] != 'Subscription failed') {
      $this->assertEquals($result['error_message'], 'Invalid Group id', 'In line ' . __LINE__);
    }
    else {
      $this->assertEquals($result['error_message'], 'Subscription failed', 'In line ' . __LINE__);
    }
  }

  /**
   * Test civicrm_mailing_group_event_subscribe with given contact ID.
   */
  public function testMailerGroupSubscribeGivenContactId() {
    $params = array(
      'first_name' => 'Test',
      'last_name' => 'Test',
      'email' => $this->_email,
      'contact_type' => 'Individual',
      'version' => $this->_apiversion,
    );
    $contactID = $this->individualCreate($params);

    $params = array(
      'email' => $this->_email,
      'group_id' => $this->_groupID,
      'contact_id' => $contactID,
      'version' => $this->_apiversion,
      'hash' => 'b15de8b64e2cec34',
      'time_stamp' => '20101212121212',
    );
    $result = civicrm_api('mailing_event_subscribe', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);
    $this->assertEquals($result['values']['contact_id'], $contactID);

    $this->contactDelete($contactID);
  }

  //-------- civicrm_mailing_group_event_unsubscribe methods-----------

  /**
   * Test civicrm_mailing_group_event_unsubscribe with wrong params.
   */
  public function testMailerGroupUnsubscribeWrongParams() {
    $params = array(
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'version' => $this->_apiversion,
      'time_stamp' => '20101212121212',
    );

    $result = $this->callAPIFailure('mailing_event_unsubscribe', 'create', $params);
    $this->assertEquals($result['error_message'], 'Queue event could not be found', 'In line ' . __LINE__);
  }

  //--------- civicrm_mailing_group_event_domain_unsubscribe methods -------

  /**
   * Test civicrm_mailing_group_event_domain_unsubscribe with wrong params.
   */
  public function testMailerGroupDomainUnsubscribeWrongParams() {
    $params = array(
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'org_unsubscribe' => 1,
      'version' => $this->_apiversion,
      'time_stamp' => '20101212121212',
    );

    $result = $this->callAPIFailure('mailing_event_unsubscribe', 'create', $params);
    $this->assertEquals($result['error_message'], 'Domain Queue event could not be found', 'In line ' . __LINE__);
  }


  //----------- civicrm_mailing_group_event_resubscribe methods--------

  /**
   * Test civicrm_mailing_group_event_resubscribe with wrong params type.
   */

  /**
   * Test civicrm_mailing_group_event_resubscribe with wrong params.
   */
  public function testMailerGroupResubscribeWrongParams() {
    $params = array(
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'org_unsubscribe' => 'test',
      'version' => $this->_apiversion,
      'time_stamp' => '20101212121212',
    );
    $result = $this->callAPIFailure('mailing_event_resubscribe', 'create', $params);
    $this->assertEquals($result['error_message'], 'Queue event could not be found', 'In line ' . __LINE__);
  }

  //------------------------ success case ---------------------

  /**
   * Test civicrm_mailing_group_event_subscribe and civicrm_mailing_event_confirm functions - success expected.
   */
  public function testMailerProcess() {
    $params = array(
      'first_name' => 'Test',
      'last_name' => 'Test',
      'email' => $this->_email,
      'contact_type' => 'Individual',
      'version' => $this->_apiversion,
    );
    $contactID = $this->individualCreate($params);

    $params = array(
      'email' => $this->_email,
      'group_id' => $this->_groupID,
      'contact_id' => $contactID,
      'version' => $this->_apiversion,
      'hash' => 'b15de8b64e2cec34',
      'time_stamp' => '20101212121212',
    );
    $result = civicrm_api('mailing_event_subscribe', 'create', $params);

    $this->assertAPISuccess($result, 'in line ' . __LINE__);
    $this->assertEquals($result['values']['contact_id'], $contactID);

    $params = array(
      'contact_id' => $result['values']['contact_id'],
      'subscribe_id' => $result['values']['subscribe_id'],
      'hash' => $result['values']['hash'],
      'version' => $this->_apiversion,
      'time_stamp' => '20101212121212',
      'event_subscribe_id' => $result['values']['subscribe_id'],
    );


    $result = civicrm_api('mailing_event_confirm', 'create', $params);

    $this->assertAPISuccess($result, 'in line ' . __LINE__);
    $this->contactDelete($contactID);
  }
}

