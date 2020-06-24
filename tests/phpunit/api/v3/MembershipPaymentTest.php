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
 *  Test APIv3 civicrm_membership_payment* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Member
 * @group headless
 */
class api_v3_MembershipPaymentTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $_contactID;
  protected $_contributionTypeID;
  protected $_membershipTypeID;
  protected $_membershipStatusID;
  protected $_contribution = [];

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);

    $this->_contactID = $this->organizationCreate(NULL);
    $this->_membershipTypeID = $this->membershipTypeCreate(['member_of_contact_id' => $this->_contactID]);
    $this->_membershipStatusID = $this->membershipStatusCreate('test status');
    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, TRUE, 'name');
    $params = [
      'contact_id' => $this->_contactID,
      'currency' => 'USD',
      'financial_type_id' => 1,
      'contribution_status_id' => 1,
      'contribution_page_id' => NULL,
      'payment_instrument_id' => 1,
      'source' => 'STUDENT',
      'receive_date' => '20080522000000',
      'receipt_date' => '20080522000000',
      'total_amount' => 200.00,
      'trxn_id' => '22ereerwww322323',
      'invoice_id' => '22ed39c9e9ee6ef6031621ce0eafe6da70',
      'thankyou_date' => '20080522',
    ];

    $this->_contribution = $this->callAPISuccess('contribution', 'create', $params);
  }

  ///////////////// civicrm_membership_payment_create methods

  /**
   * Test civicrm_membership_payment_create with empty params.
   */
  public function testCreateEmptyParams() {
    $this->callAPIFailure('membership_payment', 'create', [], 'Mandatory key(s) missing from params array: membership_id, contribution_id');
  }

  /**
   * Test civicrm_membership_payment_create - success expected.
   */
  public function testCreate() {
    $contactId = $this->individualCreate();

    $params = [
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2006-01-21',
      'start_date' => '2006-01-21',
      'end_date' => '2006-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    ];

    $membership = $this->callAPISuccess('membership', 'create', $params);

    $params = [
      'contribution_id' => $this->_contribution['id'],
      'membership_id' => $membership['id'],
    ];
    $result = $this->callAPIAndDocument('membership_payment', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][$result['id']]['membership_id'], $membership['id'], 'Check Membership Id in line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['contribution_id'], $this->_contribution['id'], 'Check Contribution Id in line ' . __LINE__);

  }

  ///////////////// civicrm_membershipPayment_get methods

  /**
   * Test civicrm_membershipPayment_get - success expected.
   */
  public function testGet() {
    $contactId = $this->individualCreate();
    $params = [
      'contact_id' => $contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
    ];

    $membership = $this->callAPISuccess('membership', 'create', $params);

    $params = [
      'contribution_id' => $this->_contribution['id'],
      'membership_id' => $membership['id'],
    ];
    $this->callAPISuccess('membership_payment', 'create', $params);

    $result = $this->callAPIAndDocument('membership_payment', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][$result['id']]['membership_id'], $params['membership_id'], 'Check Membership Id');
    $this->assertEquals($result['values'][$result['id']]['contribution_id'], $params['contribution_id'], 'Check Contribution Id');
  }

}
