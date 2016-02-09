<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @group headless
 */
class CRM_Contribute_ActionMapping_ByTypeTest extends \Civi\ActionSchedule\AbstractMappingTest {

  /**
   * Generate a list of test cases, where each is a distinct combination of
   * data, schedule-rules, and schedule results.
   *
   * @return array
   *   - targetDate: string; eg "2015-02-01 00:00:01"
   *   - setupFuncs: string, space-separated list of setup functions
   *   - messages: array; each item is a message that's expected to be sent
   *     each message may include keys:
   *        - time: approximate time (give or take a few seconds)
   *        - recipients: array of emails
   *        - subject: regex
   */
  public function createTestCases() {
    $cs = array();

    $cs[] = array(
      '2015-02-01 00:00:00',
      'addAliceDues addBobDonation scheduleForDues startOnTime useHelloFirstName',
      array(
        array(
          'time' => '2015-02-01 00:00:00',
          'to' => array('alice@example.org'),
          'subject' => '/Hello, Alice.*via subject/',
        ),
      ),
    );

    $cs[] = array(
      '2015-02-01 00:00:00',
      'addAliceDues addBobDonation scheduleForAny startOnTime useHelloFirstName',
      array(
        array(
          'time' => '2015-02-01 00:00:00',
          'to' => array('alice@example.org'),
          'subject' => '/Hello, Alice.*via subject/',
        ),
        array(
          'time' => '2015-02-01 00:00:00',
          'to' => array('bob@example.org'),
          'subject' => '/Hello, Bob.*via subject/',
        ),
      ),
    );

    $cs[] = array(
      '2015-02-02 00:00:00',
      'addAliceDues addBobDonation scheduleForDonation startWeekBefore repeatTwoWeeksAfter useHelloFirstName',
      array(
        array(
          'time' => '2015-01-26 00:00:00',
          'to' => array('bob@example.org'),
          'subject' => '/Hello, Bob.*via subject/',
        ),
        array(
          'time' => '2015-02-02 00:00:00',
          'to' => array('bob@example.org'),
          'subject' => '/Hello, Bob.*via subject/',
        ),
        array(
          'time' => '2015-02-09 00:00:00',
          'to' => array('bob@example.org'),
          'subject' => '/Hello, Bob.*via subject/',
        ),
        array(
          'time' => '2015-02-16 00:00:00',
          'to' => array('bob@example.org'),
          'subject' => '/Hello, Bob.*via subject/',
        ),
      ),
    );

    $cs[] = array(
      '2015-02-03 00:00:00',
      'addAliceDues addBobDonation scheduleForSoftCreditor startWeekAfter useHelloFirstName',
      array(
        array(
          'time' => '2015-02-10 00:00:00',
          'to' => array('carol@example.org'),
          'subject' => '/Hello, Carol.*via subject/',
        ),
      ),
    );

    return $cs;
  }

  public function addAliceDues() {
    $this->callAPISuccess('Contribution', 'create', array(
      'contact_id' => $this->contacts['alice']['id'],
      'receive_date' => date('Ymd', strtotime($this->targetDate)),
      'total_amount' => '100',
      'financial_type_id' => 1,
      'non_deductible_amount' => '10',
      'fee_amount' => '5',
      'net_amount' => '95',
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'soft_credit' => array(
        '1' => array(
          'contact_id' => $this->contacts['carol']['id'],
          'amount' => 50,
          'soft_credit_type_id' => 3,
        ),
      ),
    ));
  }

  public function addBobDonation() {
    $this->callAPISuccess('Contribution', 'create', array(
      'contact_id' => $this->contacts['bob']['id'],
      'receive_date' => date('Ymd', strtotime($this->targetDate)),
      'total_amount' => '150',
      'financial_type_id' => 2,
      'non_deductible_amount' => '10',
      'fee_amount' => '5',
      'net_amount' => '145',
      'source' => 'SSF',
      'contribution_status_id' => 2,
    ));
  }

  public function scheduleForDues() {
    $this->schedule->mapping_id = CRM_Contribute_ActionMapping_ByType::MAPPING_ID;
    $this->schedule->start_action_date = 'receive_date';
    $this->schedule->entity_value = CRM_Utils_Array::implodePadded(array(1));
    $this->schedule->entity_status = CRM_Utils_Array::implodePadded(array(1));
  }

  public function scheduleForDonation() {
    $this->schedule->mapping_id = CRM_Contribute_ActionMapping_ByType::MAPPING_ID;
    $this->schedule->start_action_date = 'receive_date';
    $this->schedule->entity_value = CRM_Utils_Array::implodePadded(array(2));
    $this->schedule->entity_status = CRM_Utils_Array::implodePadded(NULL);
  }

  public function scheduleForAny() {
    $this->schedule->mapping_id = CRM_Contribute_ActionMapping_ByType::MAPPING_ID;
    $this->schedule->start_action_date = 'receive_date';
    $this->schedule->entity_value = CRM_Utils_Array::implodePadded(NULL);
    $this->schedule->entity_status = CRM_Utils_Array::implodePadded(NULL);
  }

  public function scheduleForSoftCreditor() {
    $this->schedule->mapping_id = CRM_Contribute_ActionMapping_ByType::MAPPING_ID;
    $this->schedule->start_action_date = 'receive_date';
    $this->schedule->entity_value = CRM_Utils_Array::implodePadded(NULL);
    $this->schedule->entity_status = CRM_Utils_Array::implodePadded(NULL);
    $this->schedule->limit_to = 1;
    $this->schedule->recipient = 'soft_credit_type';
    $this->schedule->recipient_listing = CRM_Utils_Array::implodePadded(array(3));
  }

}
