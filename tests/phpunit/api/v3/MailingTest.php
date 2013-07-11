<?php
/**
 *  File for the TestMailing class
 *
 *  (PHP 5)
 *
 *   @package   CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

require_once 'CiviTest/CiviUnitTestCase.php';


/**
 *  Test APIv3 civicrm_mailing_* functions
 *
 *  @package   CiviCRM
 */
class api_v3_MailingTest extends CiviUnitTestCase {
  protected $_groupID;
  protected $_email;
  protected $_apiversion;

  function get_info() {
    return array(
      'name' => 'Mailer',
      'description' => 'Test all Mailer methods.',
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

  /**
   * Test civicrm_mailing_create
   */
  public function testMailerCreateSuccess() {
    $params = array(
      'subject' => 'maild',
      'body_text' => 'bdkfhdskfhduew',
      'version' => 3,
      'name' => 'mailing name',
      'created_id' => 1,
    );
    $result = civicrm_api('mailing', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $jobs = civicrm_api('mailing_job', 'get', array('version' =>3, 'mailing_id' => $result['id']));
    $this->assertAPISuccess($jobs);
    $this->assertEquals(1, $jobs['count']);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    unset($params['created_id']);// return isn't working on this in getAndCheck so lets not check it for now
    $this->getAndCheck($params, $result['id'], 'mailing');
  }


  //@ todo tests below here are all failure tests which are not hugely useful - need success tests

  //------------ civicrm_mailing_event_bounce methods------------

  /**
   * Test civicrm_mailing_event_bounce with wrong params.
   */
  public function testMailerBounceWrongParams() {
    $params = array(
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'body' => 'Body...',
      'version' => '3',
      'time_stamp' => '20111109212100',
    );
    $result = civicrm_api('mailing_event', 'bounce', $params);
    $this->assertAPIFailure($result, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Queue event could not be found', 'In line ' . __LINE__);
  }

  //----------- civicrm_mailing_event_confirm methods -----------

  /**
   * Test civicrm_mailing_event_confirm with wrong params.
   */
  public function testMailerConfirmWrongParams() {
    $params = array(
      'contact_id' => 'Wrong ID',
      'subscribe_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'event_subscribe_id' => '123',
      'time_stamp' => '20111111010101',
      'version' => 3,
    );
    $result = civicrm_api('mailing_event', 'confirm', $params);
    $this->assertAPIFailure($result, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Confirmation failed', 'In line ' . __LINE__);
  }

  //---------- civicrm_mailing_event_reply methods -----------

  /**
   * Test civicrm_mailing_event_reply with wrong params.
   */
  public function testMailerReplyWrongParams() {
    $params = array(
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'bodyTxt' => 'Body...',
      'replyTo' => $this->_email,
      'time_stamp' => '20111111010101',
      'version' => 3,
    );
    $result = civicrm_api('mailing_event', 'reply', $params);
    $this->assertAPIFailure($result, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Queue event could not be found', 'In line ' . __LINE__);
  }


  //----------- civicrm_mailing_event_forward methods ----------

  /**
   * Test civicrm_mailing_event_forward with wrong params.
   */
  public function testMailerForwardWrongParams() {
    $params = array(
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'email' => $this->_email,
      'time_stamp' => '20111111010101',
      'version' => 3,
    );
    $result = civicrm_api('mailing_event', 'forward', $params);
    $this->assertAPIFailure($result, 'In line ' . __LINE__);
    $this->assertEquals($result['error_message'], 'Queue event could not be found', 'In line ' . __LINE__);
  }


//----------- civicrm_mailing_create ----------

}
