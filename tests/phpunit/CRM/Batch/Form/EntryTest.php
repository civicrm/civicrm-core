<?php

/**
 *  File for the EntryTest class
 *
 *  (PHP 5)
 *
 * @package   CiviCRM
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

/**
 *  Test CRM/Member/BAO Membership Log add , delete functions
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Batch_Form_EntryTest extends CiviUnitTestCase {

  /**
   * Membership type name used in test function.
   *
   * @var string
   */
  protected $_membershipTypeName = NULL;

  /**
   * Membership type id used in test function.
   *
   * @var string
   */
  protected $_membershipTypeID = NULL;

  /**
   * Contact id used in test function.
   *
   * @var string
   */
  protected $_contactID = NULL;
  /**
   * Contact id used in test function.
   *
   * @var string
   */
  protected $_contactID2 = NULL;

  /**
   * Contact id used in test function.
   *
   * @var string
   */
  protected $_contactID3 = NULL;

  /**
   * Contact id used in test function.
   *
   * @var string
   */
  protected $_contactID4 = NULL;

  public function setUp() {
    parent::setUp();

    $params = [
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
      'name_a_b' => 'Test Employee of',
      'name_b_a' => 'Test Employer of',
    ];
    $this->_relationshipTypeId = $this->relationshipTypeCreate($params);
    $this->_orgContactID = $this->organizationCreate();
    $this->_financialTypeId = 1;
    $this->_membershipTypeName = 'Mickey Mouse Club Member';
    $params = [
      'name' => $this->_membershipTypeName,
      'description' => NULL,
      'minimum_fee' => 1500,
      'duration_unit' => 'year',
      'member_of_contact_id' => $this->_orgContactID,
      'period_type' => 'fixed',
      'duration_interval' => 1,
      'financial_type_id' => $this->_financialTypeId,
      'relationship_type_id' => $this->_relationshipTypeId,
      'visibility' => 'Public',
      'is_active' => 1,
      'fixed_period_start_day' => 101,
      'fixed_period_rollover_day' => 1231,
      'domain_id' => CRM_Core_Config::domainID(),
    ];
    $membershipType = $this->callAPISuccess('membership_type', 'create', $params);
    $this->_membershipTypeID = $membershipType['id'];

    $this->_orgContactID2 = $this->organizationCreate();
    $params = [
      'name' => 'General',
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'member_of_contact_id' => $this->_orgContactID2,
      'domain_id' => 1,
      'financial_type_id' => 1,
      'is_active' => 1,
      'sequential' => 1,
      'visibility' => 'Public',
    ];
    $membershipType2 = $this->callAPISuccess('membership_type', 'create', $params);
    $this->_membershipTypeID2 = $membershipType2['id'];

    $this->_membershipStatusID = $this->membershipStatusCreate('test status');
    $this->_contactID = $this->individualCreate();
    $contact2Params = [
      'first_name' => 'Anthonita',
      'middle_name' => 'J.',
      'last_name' => 'Anderson',
      'prefix_id' => 3,
      'suffix_id' => 3,
      'email' => 'b@c.com',
      'contact_type' => 'Individual',
    ];
    $this->_contactID2 = $this->individualCreate($contact2Params);
    $this->_contactID3 = $this->individualCreate(['first_name' => 'bobby', 'email' => 'c@d.com']);
    $this->_contactID4 = $this->individualCreate(['first_name' => 'bobbynita', 'email' => 'c@de.com']);

    $session = CRM_Core_Session::singleton();
    $session->set('dateTypes', 1);
    $this->_sethtmlGlobals();

  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $this->relationshipTypeDelete($this->_relationshipTypeId);
    if ($this->callAPISuccessGetCount('membership', ['id' => $this->_membershipTypeID])) {
      $this->membershipTypeDelete(['id' => $this->_membershipTypeID]);
    }
    if ($this->callAPISuccessGetCount('MembershipStatus', ['id' => $this->_membershipStatusID])) {
      $this->membershipStatusDelete($this->_membershipStatusID);
    }
    $this->contactDelete($this->_contactID);
    $this->contactDelete($this->_contactID2);
    $this->contactDelete($this->_orgContactID);
  }

  /**
   *  Test Import.
   *
   * @param string $thousandSeparator
   *
   * @dataProvider getThousandSeparators
   */
  public function testProcessMembership($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);

    $form = new CRM_Batch_Form_Entry();
    $profileID = $this->callAPISuccessGetValue('UFGroup', ['return' => 'id', 'name' => 'membership_batch_entry']);
    $form->_fields = CRM_Core_BAO_UFGroup::getFields($profileID, FALSE, CRM_Core_Action::VIEW);

    $params = $this->getMembershipData();
    $this->assertTrue($form->testProcessMembership($params));
    $result = $this->callAPISuccess('membership', 'get', []);
    $this->assertEquals(3, $result['count']);
    //check start dates #1 should default to 1 Jan this year, #2 should be as entered
    $this->assertEquals(date('Y-m-d', strtotime('first day of January 2013')), $result['values'][1]['start_date']);
    $this->assertEquals('2013-02-03', $result['values'][2]['start_date']);

    //check start dates #1 should default to 1 Jan this year, #2 should be as entered
    $this->assertEquals(date('Y-m-d', strtotime('last day of December 2013')), $result['values'][1]['end_date']);
    $this->assertEquals(date('Y-m-d', strtotime('last day of December 2013')), $result['values'][2]['end_date']);
    $this->assertEquals('2013-12-01', $result['values'][3]['end_date']);

    //check start dates #1 should default to 1 Jan this year, #2 should be as entered
    $this->assertEquals(date('Y-m-d', strtotime('07/22/2013')), $result['values'][1]['join_date']);
    $this->assertEquals(date('Y-m-d', strtotime('07/03/2013')), $result['values'][2]['join_date']);
    $this->assertEquals(date('Y-m-d', strtotime('now')), $result['values'][3]['join_date']);
    $result = $this->callAPISuccess('contribution', 'get', ['return' => ['total_amount', 'trxn_id']]);
    $this->assertEquals(3, $result['count']);
    foreach ($result['values'] as $key => $contribution) {
      $this->assertEquals($this->callAPISuccess('line_item', 'getvalue', [
        'contribution_id' => $contribution['id'],
        'return' => 'line_total',

      ]), $contribution['total_amount']);
      $this->assertEquals(1500, $contribution['total_amount']);
      $this->assertEquals($params['field'][$key]['trxn_id'], $contribution['trxn_id']);
    }
  }

  /**
   *  Test Contribution Import.
   *
   * @param $thousandSeparator
   *
   * @dataProvider getThousandSeparators
   */
  public function testProcessContribution($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $this->offsetDefaultPriceSet();
    $form = new CRM_Batch_Form_Entry();
    $params = $this->getContributionData();
    $this->assertTrue($form->testProcessContribution($params));
    $result = $this->callAPISuccess('contribution', 'get', ['return' => 'total_amount']);
    $this->assertEquals(2, $result['count']);
    foreach ($result['values'] as $contribution) {
      $this->assertEquals($this->callAPISuccess('line_item', 'getvalue', [
        'contribution_id' => $contribution['id'],
        'return' => 'line_total',

      ]), $contribution['total_amount']);
    }
  }

  /**
   * CRM-18000 - Test start_date, end_date after renewal
   */
  public function testMembershipRenewalDates() {
    $form = new CRM_Batch_Form_Entry();
    foreach ([$this->_contactID, $this->_contactID2] as $contactID) {
      $membershipParams = [
        'membership_type_id' => $this->_membershipTypeID2,
        'contact_id' => $contactID,
        'start_date' => "01/01/2015",
        'join_date' => "01/01/2010",
        'end_date' => "12/31/2015",
      ];
      $this->contactMembershipCreate($membershipParams);
    }

    $params = $this->getMembershipData();
    //ensure membership renewal
    $params['member_option'] = [
      1 => 2,
      2 => 2,
    ];
    $params['field'][1]['membership_type'] = [0 => $this->_orgContactID2, 1 => $this->_membershipTypeID2];
    $params['field'][1]['receive_date'] = date('Y-m-d');

    // explicitly specify start and end dates
    $params['field'][2]['membership_type'] = [0 => $this->_orgContactID2, 1 => $this->_membershipTypeID2];
    $params['field'][2]['membership_start_date'] = "2016-04-01";
    $params['field'][2]['membership_end_date'] = "2017-03-31";
    $params['field'][2]['receive_date'] = "2016-04-01";

    $this->assertTrue($form->testProcessMembership($params));
    $result = $this->callAPISuccess('membership', 'get', []);

    // renewal dates should be from current if start_date and end_date is passed as NULL
    $this->assertEquals(date('Y-m-d'), $result['values'][1]['start_date']);
    $endDate = date("Y-m-d", strtotime(date("Y-m-d") . " +1 year -1 day"));
    $this->assertEquals($endDate, $result['values'][1]['end_date']);

    // verify if the modified dates asserts with the dates passed above
    $this->assertEquals('2016-04-01', $result['values'][2]['start_date']);
    $this->assertEquals('2017-03-31', $result['values'][2]['end_date']);
  }

  /**
   * Data provider for test process membership.
   * @return array
   */
  public function getMembershipData() {

    return [
      'batch_id' => 4,
      'primary_profiles' => [1 => NULL, 2 => NULL, 3 => NULL],
      'primary_contact_id' => [
        1 => $this->_contactID,
        2 => $this->_contactID2,
        3 => $this->_contactID3,
      ],
      'field' => [
        1 => [
          'membership_type' => [0 => $this->_orgContactID, 1 => $this->_membershipTypeID],
          'join_date' => '2013-07-22',
          'membership_start_date' => NULL,
          'membership_end_date' => NULL,
          'membership_source' => NULL,
          'financial_type' => 2,
          'total_amount' => $this->formatMoneyInput(1500),
          'receive_date' => '2013-07-24',
          'receive_date_time' => NULL,
          'payment_instrument' => 1,
          'trxn_id' => 'TX101',
          'check_number' => NULL,
          'contribution_status_id' => 1,
        ],
        2 => [
          'membership_type' => [0 => $this->_orgContactID, 1 => $this->_membershipTypeID],
          'join_date' => '2013-07-03',
          'membership_start_date' => '2013-02-03',
          'membership_end_date' => NULL,
          'membership_source' => NULL,
          'financial_type' => 2,
          'total_amount' => $this->formatMoneyInput(1500),
          'receive_date' => '2013-07-17',
          'receive_date_time' => NULL,
          'payment_instrument' => NULL,
          'trxn_id' => 'TX102',
          'check_number' => NULL,
          'contribution_status_id' => 1,
        ],
        // no join date, coded end date
        3 => [
          'membership_type' => [0 => $this->_orgContactID, 1 => $this->_membershipTypeID],
          'join_date' => NULL,
          'membership_start_date' => NULL,
          'membership_end_date' => '2013-12-01',
          'membership_source' => NULL,
          'financial_type' => 2,
          'total_amount' => $this->formatMoneyInput(1500),
          'receive_date' => '2013-07-17',
          'receive_date_time' => NULL,
          'payment_instrument' => NULL,
          'trxn_id' => 'TX103',
          'check_number' => NULL,
          'contribution_status_id' => 1,
        ],

      ],
      'actualBatchTotal' => 0,

    ];
  }

  /**
   * @param $thousandSeparator
   *
   * @return array
   */
  public function getContributionData($thousandSeparator = '.') {
    return [
      //'batch_id' => 4,
      'primary_profiles' => [1 => NULL, 2 => NULL, 3 => NULL],
      'primary_contact_id' => [
        1 => $this->_contactID,
        2 => $this->_contactID2,
        3 => $this->_contactID3,
      ],
      'field' => [
        1 => [
          'financial_type' => 1,
          'total_amount' => $this->formatMoneyInput(1500.15),
          'receive_date' => '2013-07-24',
          'receive_date_time' => NULL,
          'payment_instrument' => 1,
          'check_number' => NULL,
          'contribution_status_id' => 1,
        ],
        2 => [
          'financial_type' => 1,
          'total_amount' => $this->formatMoneyInput(1500.15),
          'receive_date' => '2013-07-24',
          'receive_date_time' => NULL,
          'payment_instrument' => 1,
          'check_number' => NULL,
          'contribution_status_id' => 1,
        ],
      ],
      'actualBatchTotal' => $this->formatMoneyInput(3000.30),

    ];
  }

}
