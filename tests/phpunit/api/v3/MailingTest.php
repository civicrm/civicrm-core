<?php
/*
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
  protected $_apiversion = 3;
  protected $_params = array();
  protected $_entity = 'Mailing';


  function get_info() {
    return array(
      'name' => 'Mailer',
      'description' => 'Test all Mailer methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    parent::setUp();
    CRM_Mailing_BAO_MailingJob::$mailsProcessed = 0; // DGW
    $this->_groupID = $this->groupCreate();
    $this->_email = 'test@test.test';
    $this->_params = array(
      'subject' => 'maild',
      'body_text' => 'bdkfhdskfhduew',
      'name' => 'mailing name',
      'created_id' => 1,
    );
  }

  function tearDown() {
    $this->groupDelete($this->_groupID);
    CRM_Mailing_BAO_MailingJob::$mailsProcessed = 0; // DGW
  }

  /**
   * Test civicrm_mailing_create
   */
  public function testMailerCreateSuccess() {
    $result = $this->callAPIAndDocument('mailing', 'create', $this->_params, __FUNCTION__, __FILE__);
    $jobs = $this->callAPISuccess('mailing_job', 'get', array('mailing_id' => $result['id']));
    $this->assertEquals(1, $jobs['count']);
    unset($this->_params['created_id']); // return isn't working on this in getAndCheck so lets not check it for now
    $this->getAndCheck($this->_params, $result['id'], 'mailing');
  }

  /**
   * Test civicrm_mailing_delete
   */
  public function testMailerDeleteSuccess() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $jobs = $this->callAPIAndDocument($this->_entity, 'delete', array('id' => $result['id']), __FUNCTION__, __FILE__);
    $this->assertAPIDeleted($this->_entity, $result['id']);
  }

  //@ todo tests below here are all failure tests which are not hugely useful - need success tests

  //------------ civicrm_mailing_event_bounce methods------------

  /**
   * Test civicrm_mailing_event_bounce with wrong params.
   * Note that tests like this are slightly better than no test but an
   * api function cannot be considered supported  / 'part of the api' without a
   * success test
   */
  public function testMailerBounceWrongParams() {
    $params = array(
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'body' => 'Body...',
      'time_stamp' => '20111109212100',
    );
    $result = $this->callAPIFailure('mailing_event', 'bounce', $params,
      'Queue event could not be found'
    );
  }

  //----------- civicrm_mailing_event_confirm methods -----------

  /**
   * Test civicrm_mailing_event_confirm with wrong params.
   * Note that tests like this are slightly better than no test but an
   * api function cannot be considered supported  / 'part of the api' without a
   * success test
   */
  public function testMailerConfirmWrongParams() {
    $params = array(
      'contact_id' => 'Wrong ID',
      'subscribe_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'event_subscribe_id' => '123',
      'time_stamp' => '20111111010101',
    );
    $result = $this->callAPIFailure('mailing_event', 'confirm', $params,
      'Confirmation failed'
    );
  }

  //---------- civicrm_mailing_event_reply methods -----------

  /**
   * Test civicrm_mailing_event_reply with wrong params.
   *
   * Note that tests like this are slightly better than no test but an
   * api function cannot be considered supported  / 'part of the api' without a
   * success test
   */
  public function testMailerReplyWrongParams() {
    $params = array(
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'bodyTxt' => 'Body...',
      'replyTo' => $this->_email,
      'time_stamp' => '20111111010101',
    );
    $result = $this->callAPIFailure('mailing_event', 'reply', $params,
      'Queue event could not be found'
    );
  }


  //----------- civicrm_mailing_event_forward methods ----------

  /**
   * Test civicrm_mailing_event_forward with wrong params.
   * Note that tests like this are slightly better than no test but an
   * api function cannot be considered supported  / 'part of the api' without a
   * success test
   */
  public function testMailerForwardWrongParams() {
    $params = array(
      'job_id' => 'Wrong ID',
      'event_queue_id' => 'Wrong ID',
      'hash' => 'Wrong Hash',
      'email' => $this->_email,
      'time_stamp' => '20111111010101',
    );
    $result = $this->callAPIFailure('mailing_event', 'forward', $params,
      'Queue event could not be found'
    );
  }

//----------- civicrm_mailing_create ----------

}
