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
 *  Test APIv3 civicrm_mailingab_* functions
 *
 * @package   CiviCRM
 */
class api_v3_MailingABTest extends CiviUnitTestCase {
  protected $_mailingID_A;
  protected $_mailingID_B;
  protected $_mailingID_C;
  protected $_params;
  protected $_apiversion = 3;
  protected $_entity = 'MailingAB';


  /**
   * @return array
   */
  function get_info() {
    return array(
      'name' => 'Mailer',
      'description' => 'Test all Mailer methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->_mailingID_A = $this->createMailing();
    $this->_mailingID_B = $this->createMailing();
    $this->_mailingID_C = $this->createMailing();
    $this->_groupID = $this->groupCreate();

    $this->_params = array(
      'mailing_id_a' => $this->_mailingID_A,
      'mailing_id_b' => $this->_mailingID_B,
      'mailing_id_c' => $this->_mailingID_C,
      'testing_criteria_id' => 1,
      'winner_criteria_id' => 1,
      'declare_winning_time' => '+2 days',
      'group_percentage' => 10,
    );
  }

  /**
   * Test civicrm_mailing_create
   */
  public function testMailingABCreateSuccess() {
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertTrue(is_numeric($result['id']), "In line " . __LINE__);
    $this->assertEquals($this->_params['group_percentage'], $result['values'][$result['id']]['group_percentage']);
  }

  /**
   * Test civicrm_mailing_delete
   */
  public function testMailerDeleteSuccess() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);

    $this->assertDBQuery(1, "SELECT count(*) FROM civicrm_mailing_abtesting WHERE id = %1", array(
      1 => array($result['id'], 'Integer'),
    ));
    $this->assertDBQuery(3, "SELECT count(*) FROM civicrm_mailing WHERE id IN (%1,%2,%3)", array(
      1 => array($this->_mailingID_A, 'Integer'),
      2 => array($this->_mailingID_B, 'Integer'),
      3 => array($this->_mailingID_C, 'Integer'),
    ));

    $this->callAPISuccess($this->_entity, 'delete', array('id' => $result['id']));

    $this->assertDBQuery(0, "SELECT count(*) FROM civicrm_mailing_abtesting WHERE id = %1", array(
      1 => array($result['id'], 'Integer'),
    ));
    $this->assertDBQuery(0, "SELECT count(*) FROM civicrm_mailing WHERE id IN (%1,%2,%3)", array(
      1 => array($this->_mailingID_A, 'Integer'),
      2 => array($this->_mailingID_B, 'Integer'),
      3 => array($this->_mailingID_C, 'Integer'),
    ));
  }

  public function testMailingABRecipientsUpdate() {
    //create 100 contacts for group $this->_groupID
    $totalGroupContacts = 100;

    $result = $this->groupContactCreate($this->_groupID, $totalGroupContacts);
    //check if 100 group contacts are included on desired group
    $this->assertEquals($totalGroupContacts, $result['added'], "in line " . __LINE__);

    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $totalSelectedContacts = round(($totalGroupContacts * $result['values'][$result['id']]['group_percentage'])/100);

    $params = array('id' => $result['id'], 'groups' => array('include' => array($this->_groupID)));
    $this->callAPISuccess('MailingAB', 'recipients_update', $params);

    //check total number of A/B mail recipients is what selected percentage of Mail C
    $countA = $this->callAPISuccess('MailingRecipients', 'getcount', array('mailing_id' =>  $this->_mailingID_A));
    $this->assertEquals($countA, $totalSelectedContacts, "in line " . __LINE__);
    $countB = $this->callAPISuccess('MailingRecipients', 'getcount', array('mailing_id' =>  $this->_mailingID_B));
    $this->assertEquals($countB, $totalSelectedContacts, "in line " . __LINE__);
  }

}
