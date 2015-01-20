<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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

/**
 * File for the CiviCRM APIv3 job functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Job
 *
 * @copyright CiviCRM LLC (c) 2004-2014
 * @version $Id: Job.php 30879 2010-11-22 15:45:55Z shot $
 *
 */
require_once 'CiviTest/CiviUnitTestCase.php';
//@todo - why doesn't class loader find these (I tried renaming)
require_once 'CiviTest/CiviMailUtils.php';

/**
 * Class api_v3_JobTest
 */
class api_v3_JobProcessMailingTest extends CiviUnitTestCase {
  protected $_apiversion = 3;

  public $DBResetRequired = FALSE;
  public $_entity = 'Job';
  public $_params = array();
  private $_groupID;
  private $_email;

  /**
   * @var CiviMailUtils
   */
  private $_mut;

  public function setUp() {
    parent::setUp();
    $this->useTransaction();
    CRM_Mailing_BAO_MailingJob::$mailsProcessed = 0; // DGW
    $this->_groupID = $this->groupCreate();
    $this->_email = 'test@test.test';
    $this->_params = array(
      'subject' => 'Accidents in cars cause children',
      'body_text' => 'BEWARE children need regular infusions of toys',
      'name' => 'mailing name',
      'created_id' => 1,
      'groups' => array('include' => array($this->_groupID)),
    );
    $this->_mut = new CiviMailUtils($this, TRUE);
    $this->callAPISuccess('mail_settings', 'get', array('api.mail_settings.create' => array('domain' => 'chaos.org')));
  }

  /**
   */
  public function tearDown() {
    $this->_mut->stop();
    //    $this->quickCleanup(array('civicrm_mailing', 'civicrm_mailing_job', 'civicrm_contact'));
    CRM_Utils_Hook::singleton()->reset();
    CRM_Mailing_BAO_MailingJob::$mailsProcessed = 0; // DGW
    parent::tearDown();

  }

  /**
   * Check mailing is sent
   */
  public function testProcessMailing() {
    $this->createContactsInGroup(10, $this->_groupID);
    CRM_Core_Config::singleton()->mailerBatchLimit = 2;
    $this->callAPISuccess('mailing', 'create', $this->_params);
    $this->callAPISuccess('job', 'process_mailing', array());
    $this->_mut->assertRecipients($this->getRecipients(1, 2));
  }

  /**
   * @param int $count
   * @param int $groupID
   */
  public function createContactsInGroup($count, $groupID) {
    for ($i = 1; $i <= $count; $i++) {
      $contactID = $this->individualCreate(array('first_name' => $count, 'email' => 'mail' . $i . '@nul.com'));
      $this->callAPISuccess('group_contact', 'create', array(
          'contact_id' => $contactID,
          'group_id' => $groupID,
          'status' => 'Added',
        ));
    }
  }

  /**
   * @param int $start
   * @param int $count
   *
   * @return array
   */
  public function getRecipients($start, $count) {
    $recipients = array();
    for ($i = $start; $i < ($start + $count); $i++) {
      $recipients[][0] = 'mail' . $i . '@nul.com';
    }
    return $recipients;
  }

}
