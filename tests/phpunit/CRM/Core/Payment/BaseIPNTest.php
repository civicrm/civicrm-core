<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Class CRM_Core_Payment_BaseIPNTest
 * @group headless
 */
class CRM_Core_Payment_BaseIPNTest extends CiviUnitTestCase {

  protected $_financialTypeId;
  protected $_contributionParams;
  protected $_contactId;
  protected $_contributionId;
  protected $_participantId;
  protected $_pledgeId;
  protected $_eventId;
  protected $_processorId;
  protected $_contributionRecurParams;
  protected $_paymentProcessor;

  /**
   * Parameters to create a membership.
   *
   * @var array
   */
  protected $_membershipParams = array();

  /**
   * IPN instance.
   *
   * @var CRM_Core_Payment_BaseIPN
   */
  protected $IPN;
  protected $_recurId;
  protected $_membershipId;
  protected $input;
  protected $ids;
  protected $objects;

  /**
   * @var int
   */
  protected $_membershipTypeID;

  /**
   * @var int
   */
  protected $_membershipStatusID;
  public $DBResetRequired = FALSE;

  /**
   * Setup function.
   */
  public function setUp() {
    parent::setUp();
    $this->_processorId = $this->paymentProcessorAuthorizeNetCreate(array('is_test' => 0));
    $this->input = $this->ids = $this->objects = array();
    $this->IPN = new CRM_Core_Payment_AuthorizeNetIPN($this->input);

    $this->_contactId = $this->individualCreate();
    $this->ids['contact'] = $this->_contactId;
    $this->_financialTypeId = 1;

    $this->_contributionParams = array(
      'contact_id' => $this->_contactId,
      'financial_type_id' => $this->_financialTypeId,
      'receive_date' => date('Ymd'),
      'total_amount' => 150.00,
      'invoice_id' => 'c8acb91e080ad7bd8a2adc119c192885',
      'currency' => 'USD',
      'contribution_recur_id' => $this->_recurId,
      'contribution_status_id' => 2,
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $this->_contributionParams);
    $this->_contributionId = $contribution['id'];

    $contribution = new CRM_Contribute_BAO_Contribution();
    $contribution->id = $this->_contributionId;
    $contribution->find(TRUE);
    $this->objects['contribution'] = $contribution;
  }

  /**
   * Tear down after class.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    CRM_Member_PseudoConstant::membershipType(NULL, TRUE);
    CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'name', TRUE);
  }

  /**
   * Test the LoadObjects function with recurring membership data.
   */
  public function testLoadMembershipObjects() {
    $this->_setUpMembershipObjects();
    $this->_setUpRecurringContribution();
    $this->IPN->loadObjects($this->input, $this->ids, $this->objects, FALSE, $this->_processorId);
    $this->assertFalse(empty($this->objects['membership']));
    $this->assertArrayHasKey($this->_membershipTypeID, $this->objects['membership']);
    $this->assertTrue(is_a($this->objects['membership'][$this->_membershipTypeID], 'CRM_Member_BAO_Membership'));
    $this->assertTrue(is_a($this->objects['financialType'], 'CRM_Financial_BAO_FinancialType'));
    $this->assertFalse(empty($this->objects['contributionRecur']));
    $this->assertFalse(empty($this->objects['paymentProcessor']));
  }

  /**
   * Test the LoadObjects function with recurring membership data.
   */
  public function testLoadMembershipObjectsNoLeakage() {
    $this->_setUpMembershipObjects();
    $this->_setUpRecurringContribution();
    $this->IPN->loadObjects($this->input, $this->ids, $this->objects, FALSE, $this->_processorId);
    $this->assertEquals('Anthony', $this->objects['contact']->first_name);

    $this->ids['contact'] = $this->_contactId = $this->individualCreate(array(
      'first_name' => 'Donald',
      'last_name' => 'Duck',
      'email' => 'the-don@duckville.com',
    ));
    $contribution = $this->callAPISuccess('contribution', 'create', array_merge($this->_contributionParams, array('invoice_id' => 'abc')));
    $this->_contributionId = $contribution['id'];
    $this->_setUpMembershipObjects();
    $this->input['invoiceID'] = 'abc';
    $this->IPN->loadObjects($this->input, $this->ids, $this->objects, FALSE, $this->_processorId);
    $this->assertEquals('Donald', $this->objects['contact']->first_name);
  }

  /**
   * Test the LoadObjects function with recurring membership data.
   */
  public function testLoadMembershipObjectsLoadAll() {
    $this->_setUpMembershipObjects();
    $this->_setUpRecurringContribution();
    unset($this->ids['membership']);
    $contribution = new CRM_Contribute_BAO_Contribution();
    $contribution->id = $this->_contributionId;
    $contribution->find(TRUE);
    $contribution->loadRelatedObjects($this->input, $this->ids, TRUE);
    $this->assertFalse(empty($contribution->_relatedObjects['membership']));
    $this->assertArrayHasKey($this->_membershipTypeID, $contribution->_relatedObjects['membership']);
    $this->assertTrue(is_a($contribution->_relatedObjects['membership'][$this->_membershipTypeID], 'CRM_Member_BAO_Membership'));
    $this->assertTrue(is_a($contribution->_relatedObjects['financialType'], 'CRM_Financial_BAO_FinancialType'));
    $this->assertFalse(empty($contribution->_relatedObjects['contributionRecur']));
    $this->assertFalse(empty($contribution->_relatedObjects['paymentProcessor']));
  }

  /**
   * Test the LoadObjects function with recurring membership data.
   */
  public function testsendMailMembershipObjects() {
    $this->_setUpMembershipObjects();
    $values = array();
    $this->IPN->loadObjects($this->input, $this->ids, $this->objects, FALSE, $this->_processorId);
    $msg = $this->IPN->sendMail($this->input, $this->ids, $this->objects, $values, FALSE, TRUE);
    $this->assertTrue(is_array($msg), "Message returned as an array in line");
    $this->assertEquals('Mr. Anthony Anderson II', $msg['to']);
    $this->assertContains('<p>Please print this confirmation for your records.</p>', $msg['html']);
    $this->assertContains('Membership Type: General', $msg['body']);
  }

  /**
   * Test the LoadObjects function data does not leak.
   *
   * If more than one iteration takes place the variables should not leak.
   */
  public function testSendMailMembershipObjectsNoLeakage() {
    $this->_setUpMembershipObjects();
    $values = array();
    $this->IPN->loadObjects($this->input, $this->ids, $this->objects, FALSE, $this->_processorId);
    $msg = $this->IPN->sendMail($this->input, $this->ids, $this->objects, $values, FALSE, TRUE);
    $this->assertEquals('Mr. Anthony Anderson II', $msg['to']);
    $this->assertContains('Membership Type: General', $msg['body']);

    $this->ids['contact'] = $this->_contactId = $this->individualCreate(array('prefix_id' => 'Dr.', 'first_name' => 'Donald', 'last_name' => 'Duck', 'email' => 'the-don@duckville.com'));
    $contribution = $this->callAPISuccess('contribution', 'create', array_merge($this->_contributionParams, array('invoice_id' => 'abc')));
    $this->_contributionId = $contribution['id'];

    $this->_membershipTypeID = $this->membershipTypeCreate(array('name' => 'Fowl'));
    $this->_setUpMembershipObjects();
    $this->input['invoiceID'] = 'abc';
    $this->IPN->loadObjects($this->input, $this->ids, $this->objects, FALSE, $this->_processorId);
    $this->assertEquals('Donald', $this->objects['contact']->first_name);
    $msg = $this->IPN->sendMail($this->input, $this->ids, $this->objects, $values, FALSE, TRUE);
    $this->assertEquals('Dr. Donald Duck II', $msg['to']);
    $this->assertContains('Membership Type: Fowl', $msg['body']);
  }

  /**
   * Test the LoadObjects function with recurring membership data.
   */
  public function testsendMailMembershipWithoutLoadObjects() {
    $this->_setUpMembershipObjects();
    $values = array();
    $msg = $this->IPN->sendMail($this->input, $this->ids, $this->objects, $values, FALSE, TRUE);
    $this->assertTrue(is_array($msg), "Message returned as an array in line" . __LINE__);
    $this->assertEquals('Mr. Anthony Anderson II', $msg['to']);
    $this->assertContains('<p>Please print this confirmation for your records.</p>', $msg['html']);
    $this->assertContains('Membership Type: General', $msg['body']);
  }

  /**
   * Test that loadObjects works with participant values.
   */
  public function testLoadParticipantObjects() {
    $this->_setUpParticipantObjects();
    $this->IPN->loadObjects($this->input, $this->ids, $this->objects, FALSE, $this->_processorId);
    $this->assertFalse(empty($this->objects['participant']));
    $this->assertTrue(is_a($this->objects['participant'], 'CRM_Event_BAO_Participant'));
    $this->assertTrue(is_a($this->objects['financialType'], 'CRM_Financial_BAO_FinancialType'));
    $this->assertFalse(empty($this->objects['event']));
    $this->assertTrue(is_a($this->objects['event'], 'CRM_Event_BAO_Event'));
    $this->assertTrue(is_a($this->objects['contribution'], 'CRM_Contribute_BAO_Contribution'));
    $this->assertFalse(empty($this->objects['event']->id));
  }

  /**
   * Test the LoadObjects function with a participant.
   */
  public function testComposeMailParticipant() {
    $this->_setUpParticipantObjects();
    $this->IPN->loadObjects($this->input, $this->ids, $this->objects, FALSE, $this->_processorId);
    $values = array();
    $this->assertFalse(empty($this->objects['event']));
    $msg = $this->IPN->sendMail($this->input, $this->ids, $this->objects, $values, FALSE, TRUE);
    $this->assertContains('registration has been received and your status has been updated to Attended.', $msg['body']);
    $this->assertContains('Annual CiviCRM meet', $msg['html']);
  }

  /**
   */
  public function testComposeMailParticipantObjects() {
    $this->_setUpParticipantObjects();
    $values = array();
    $msg = $this->IPN->sendMail($this->input, $this->ids, $this->objects, $values, FALSE, TRUE);
    $this->assertTrue(is_array($msg), "Message returned as an array in line" . __LINE__);
    $this->assertEquals('Mr. Anthony Anderson II', $msg['to']);
    $this->assertContains('<p>Please print this confirmation for your records.</p>', $msg['html']);
    $this->assertContains('Thank you for your participation', $msg['body']);
  }

  /**
   * Test the LoadObjects function with recurring membership data.
   */
  public function testsendMailParticipantObjectsCheckLog() {
    $this->_setUpParticipantObjects();
    $values = array();
    $mut = new CiviMailUtils($this, TRUE);
    $this->IPN->loadObjects($this->input, $this->ids, $this->objects, FALSE, $this->_processorId);
    $this->IPN->sendMail($this->input, $this->ids, $this->objects, $values, FALSE, FALSE);
    $mut->checkMailLog(array(
      'Thank you for your participation',
      'Annual CiviCRM meet',
      'Mr. Anthony Anderson II',
    ));
    $mut->stop();
  }

  /**
   * Test the LoadObjects function with recurring membership data.
   */
  public function testsendMailParticipantObjectsNoMail() {
    $this->_setUpParticipantObjects();
    $event = new CRM_Event_BAO_Event();
    $event->id = $this->_eventId;
    $event->is_email_confirm = FALSE;
    $event->save();
    $values = array();
    $tablesToTruncate = array(
      'civicrm_mailing_spool',
    );
    $this->quickCleanup($tablesToTruncate, FALSE);
    $mut = new CiviMailUtils($this, TRUE);
    $this->IPN->loadObjects($this->input, $this->ids, $this->objects, FALSE, $this->_processorId);
    $this->IPN->sendMail($this->input, $this->ids, $this->objects, $values, FALSE, FALSE);
    $mut->assertMailLogEmpty('no mail should have been send as event set to no confirm');
    $mut->stop();
  }

  /**
   * Test that loadObjects works with participant values.
   */
  public function testLoadPledgeObjects() {
    $this->_setUpPledgeObjects();
    $this->IPN->loadObjects($this->input, $this->ids, $this->objects, FALSE, $this->_processorId);
    $this->assertFalse(empty($this->objects['pledge_payment'][0]));
    $this->assertTrue(is_a($this->objects['financialType'], 'CRM_Financial_BAO_FinancialType'));
    $this->assertTrue(is_a($this->objects['contribution'], 'CRM_Contribute_BAO_Contribution'));
    $this->assertTrue(is_a($this->objects['pledge_payment'][0], 'CRM_Pledge_BAO_PledgePayment'));
    $this->assertFalse(empty($this->objects['pledge_payment'][0]->id));
    $this->assertEquals($this->_financialTypeId, $this->objects['financialType']->id);
    $this->assertEquals($this->_processorId, $this->objects['paymentProcessor']['id']);
    $this->assertEquals($this->_contributionId, $this->objects['contribution']->id);
    $this->assertEquals($this->_contactId, $this->objects['contact']->id);
    $this->assertEquals($this->_pledgeId, $this->objects['pledge_payment'][0]->pledge_id);
  }

  /**
   * Test that loadObjects works with participant values.
   */
  public function testLoadPledgeObjectsInvalidPledgeID() {
    $this->_setUpPledgeObjects();
    $this->ids['pledge_payment'][0] = 0;
    $result = $this->IPN->loadObjects($this->input, $this->ids, $this->objects, TRUE, NULL, array('return_error' => 1));
    $this->assertArrayHasKey('error_message', $result);
    $this->assertArrayNotHasKey('pledge_payment', $this->objects);
    $this->assertEquals('Could not find payment processor for contribution record: 1', $result['error_message']);
    $this->ids['pledge_payment'][0] = NULL;
    $result = $this->IPN->loadObjects($this->input, $this->ids, $this->objects, TRUE, NULL, array('return_error' => 1));
    $this->assertArrayHasKey('error_message', $result);
    $this->assertArrayNotHasKey('pledge_payment', $this->objects);
    $this->assertEquals('Could not find payment processor for contribution record: 1', $result['error_message']);
    $this->ids['pledge_payment'][0] = '';
    $result = $this->IPN->loadObjects($this->input, $this->ids, $this->objects, TRUE, NULL, array('return_error' => 1));
    $this->assertArrayHasKey('error_message', $result);
    $this->assertArrayNotHasKey('pledge_payment', $this->objects);
    $this->assertEquals('Could not find payment processor for contribution record: 1', $result['error_message']);

    $this->ids['pledge_payment'][0] = 999;
    $result = $this->IPN->loadObjects($this->input, $this->ids, $this->objects, TRUE, $this->_processorId, array('return_error' => 1));
    $this->assertArrayHasKey('error_message', $result);
    $this->assertEquals('Could not find pledge payment record: 999', $result['error_message']);
  }

  /**
   * Test the LoadObjects function with a pledge.
   */
  public function testsendMailPledge() {
    $this->_setUpPledgeObjects();
    $values = array();
    $this->IPN->loadObjects($this->input, $this->ids, $this->objects, FALSE, NULL);
    $msg = $this->IPN->sendMail($this->input, $this->ids, $this->objects, $values, FALSE, TRUE);
    $this->assertContains('Contribution Information', $msg['html']);
  }

  /**
   * Test that an error is returned if required set & no contribution page.
   */
  public function testRequiredWithoutProcessorID() {
    $this->_setUpPledgeObjects();
    $result = $this->IPN->loadObjects($this->input, $this->ids, $this->objects, TRUE, NULL, array('return_error' => 1));
    $this->assertArrayHasKey('error_message', $result);
    $this->assertEquals('Could not find payment processor for contribution record: 1', $result['error_message']);
    // error is only returned if $required set to True
    $result = $this->IPN->loadObjects($this->input, $this->ids, $this->objects, FALSE, NULL, array('return_error' => 1));
    $this->assertFalse(is_array($result));
    //check that error is not returned if error checking not set
    $result = $this->IPN->loadObjects($this->input, $this->ids, $this->objects, TRUE, NULL, array('log_error' => 1));
    $this->assertFalse(is_array($result));
  }

  /**
   * Test that an error is not if required set & no processor ID
   */
  public function testRequiredWithContributionPage() {
    $this->_setUpContributionObjects(TRUE);
    $result = $this->IPN->loadObjects($this->input, $this->ids, $this->objects, TRUE, NULL, array('return_error' => 1));
    $this->assertEquals(1, $result['is_error']);
    ;
  }

  /**
   * Test that if part of $input the payment processor loads OK.
   *
   * It's preferable to pass it in as it cannot be correctly calculated.
   */
  public function testPaymentProcessorLoadsAsParam() {
    $this->_setUpContributionObjects();
    $this->input = array_merge($this->input, array('payment_processor_id' => $this->_processorId));
    $this->assertTrue($this->IPN->loadObjects($this->input, $this->ids, $this->objects, TRUE, NULL, array('return_error' => 1)));
  }

  /**
   * Test that an error is returned if required set & contribution page exists
   */
  public function testRequiredWithContributionPageError() {
    $this->_setUpContributionObjects();
    $result = $this->IPN->loadObjects($this->input, $this->ids, $this->objects, TRUE, NULL, array('return_error' => 1));
    $this->assertArrayHasKey('error_message', $result);
    $this->assertEquals('Could not find payment processor for contribution record: 1', $result['error_message']);
    // error is only returned if $required set to True
    $result = $this->IPN->loadObjects($this->input, $this->ids, $this->objects, FALSE, NULL, array('return_error' => 1));
    $this->assertFalse(is_array($result));
    //check that error is not returned if error checking not set
    $result = $this->IPN->loadObjects($this->input, $this->ids, $this->objects, TRUE, NULL, array('log_error' => 1));
    $this->assertFalse(is_array($result));
  }

  public function testThatCancellingEventPaymentWillCancelAllAdditionalPendingParticipantsAndCreateCancellationActivities() {
    $this->_setUpParticipantObjects('Pending from incomplete transaction');
    $this->IPN->loadObjects($this->input, $this->ids, $this->objects, FALSE, $this->_processorId);
    $additionalParticipantId = $this->participantCreate(array(
      'event_id' => $this->_eventId,
      'registered_by_id' => $this->_participantId,
      'status_id' => 'Pending from incomplete transaction',
    ));

    $transaction = new CRM_Core_Transaction();
    $this->IPN->cancelled($this->objects, $transaction);

    $cancelledParticipantsCount = civicrm_api3('Participant', 'get', [
      'sequential' => 1,
      'id' => ['IN' => [$this->_participantId, $additionalParticipantId]],
      'status_id' => 'Cancelled',
    ])['count'];
    $this->assertEquals(2, $cancelledParticipantsCount);

    $cancelledActivatesCount = civicrm_api3('Activity', 'get', [
      'sequential' => 1,
      'activity_type_id' => 'Event Registration',
      'subject' => ['LIKE' => '%Cancelled%'],
      'source_record_id' => ['IN' => [$this->_participantId, $additionalParticipantId]],
    ]);

    $this->assertEquals(2, $cancelledActivatesCount['count']);
  }

  public function testThatFailedEventPaymentWillCancelAllAdditionalPendingParticipantsAndCreateCancellationActivities() {
    $this->_setUpParticipantObjects('Pending from incomplete transaction');
    $this->IPN->loadObjects($this->input, $this->ids, $this->objects, FALSE, $this->_processorId);
    $additionalParticipantId = $this->participantCreate(array(
      'event_id' => $this->_eventId,
      'registered_by_id' => $this->_participantId,
      'status_id' => 'Pending from incomplete transaction',
    ));

    $transaction = new CRM_Core_Transaction();
    $this->IPN->failed($this->objects, $transaction);

    $cancelledParticipantsCount = civicrm_api3('Participant', 'get', [
      'sequential' => 1,
      'id' => ['IN' => [$this->_participantId, $additionalParticipantId]],
      'status_id' => 'Cancelled',
    ])['count'];
    $this->assertEquals(2, $cancelledParticipantsCount);

    $cancelledActivatesCount = civicrm_api3('Activity', 'get', [
      'sequential' => 1,
      'activity_type_id' => 'Event Registration',
      'subject' => ['LIKE' => '%Cancelled%'],
      'source_record_id' => ['IN' => [$this->_participantId, $additionalParticipantId]],
    ]);

    $this->assertEquals(2, $cancelledActivatesCount['count']);
  }

  /**
   * Prepare for contribution Test - involving only contribution objects
   *
   * @param bool $contributionPage
   */
  public function _setUpContributionObjects($contributionPage = FALSE) {

    $contribution = new CRM_Contribute_BAO_Contribution();
    $contribution->id = $this->_contributionId;
    $contribution->find(TRUE);
    $contributionPageID = NULL;

    if (!empty($contributionPage)) {
      $dao = new CRM_Core_DAO();
      $contribution_page = $dao->createTestObject('CRM_Contribute_DAO_ContributionPage');
      $contribution_page->payment_processor = 1;
      $contribution_page->save();
      $contribution->contribution_page_id = $contributionPageID = $contribution_page->id;
      //for unknown reasons trying to do a find & save on a contribution with a receive_date set
      // doesn't work. This seems of minimal relevance to this test so ignoring
      // note that in tests it worked sometimes & not others - dependent on which other tests run.
      // running all CRM_Core tests caused failure as did just the single failing test. But running
      // just this class succeeds - because it actually doesn't do a mysql update on the following save
      // (unknown reason)
      unset($contribution->receive_date);
      $contribution->save();
    }

    $this->objects['contribution'] = $contribution;
    $this->input = array(
      'component' => 'contribute',
      'contribution_page_id' => $contributionPageID,
      'total_amount' => 110.00,
      'invoiceID' => "c8acb91e080ad7777a2adc119c192885",
      'contactID' => $this->_contactId,
      'contributionID' => $this->objects['contribution']->id,
    );
  }

  /**
   * Prepare for membership test.
   */
  public function _setUpMembershipObjects() {
    try {
      if (!$this->_membershipTypeID) {
        $this->_membershipTypeID = $this->membershipTypeCreate();
      }
      if (!$this->_membershipStatusID) {
        $this->_membershipStatusID = $this->membershipStatusCreate('test status');
      }
    }
    catch (Exception$e) {
      echo $e->getMessage();
    }
    CRM_Member_PseudoConstant::membershipType($this->_membershipTypeID, TRUE);
    CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'name', TRUE);
    $this->_membershipParams = array(
      'contact_id' => $this->_contactId,
      'membership_type_id' => $this->_membershipTypeID,
      'join_date' => '2009-01-21',
      'start_date' => '2009-01-21',
      'end_date' => '2009-12-21',
      'source' => 'Payment',
      'is_override' => 1,
      'status_id' => $this->_membershipStatusID,
      'version' => 3,
    );

    $membership = $this->callAPISuccess('membership', 'create', $this->_membershipParams);
    if ($this->objects['contribution']->id != $this->_contributionId) {
      $contribution = new CRM_Contribute_BAO_Contribution();
      $contribution->id = $this->_contributionId;
      $contribution->find(TRUE);
      $this->objects = array('contribution' => $contribution);
    }
    $this->_membershipId = $membership['id'];
    //we'll create membership payment here because to make setup more re-usable
    $this->callAPISuccess('membership_payment', 'create', array(
      'contribution_id' => $this->_contributionId,
      'membership_id' => $this->_membershipId,
    ));

    $this->input = array(
      'component' => 'contribute',
      'total_amount' => 150.00,
      'invoiceID' => "c8acb91e080ad7bd8a2adc119c192885",
      'contactID' => $this->_contactId,
      'contributionID' => $this->_contributionId,
      'membershipID' => $this->_membershipId,
    );

    $this->ids['membership'] = $this->_membershipId;
  }

  public function _setUpRecurringContribution() {
    $this->_contributionRecurParams = array(
      'contact_id' => $this->_contactId,
      'amount' => 150.00,
      'currency' => 'USD',
      'frequency_unit' => 'week',
      'frequency_interval' => 1,
      'installments' => 2,
      'start_date' => date('Ymd'),
      'create_date' => date('Ymd'),
      'invoice_id' => 'c8acb91e080ad7bd8a2adc119c192885',
      'contribution_status_id' => 2,
      'financial_type_id' => $this->_financialTypeId,
      'version' => 3,
      'payment_processor_id' => $this->_processorId,
    );
    $this->_recurId = $this->callAPISuccess('contribution_recur', 'create', $this->_contributionRecurParams);
    $this->_recurId = $this->_recurId['id'];
    $this->input['contributionRecurId'] = $this->_recurId;
    $this->ids['contributionRecur'] = $this->_recurId;
  }

  /**
   * Set up participant requirements for test.
   *
   * @param string $participantStatus
   *   The participant to create status
   */
  public function _setUpParticipantObjects($participantStatus = 'Attended') {
    $event = $this->eventCreate(array('is_email_confirm' => 1));

    $this->_eventId = $event['id'];
    $this->_participantId = $this->participantCreate(array(
      'event_id' => $this->_eventId,
      'contact_id' => $this->_contactId,
      'status_id' => $participantStatus,
    ));

    $this->callAPISuccess('participant_payment', 'create', array(
      'contribution_id' => $this->_contributionId,
      'participant_id' => $this->_participantId,
    ));

    $contribution = new CRM_Contribute_BAO_Contribution();
    $contribution->id = $this->_contributionId;
    $contribution->find();
    $this->objects['contribution'] = $contribution;
    $this->input = array(
      'component' => 'event',
      'total_amount' => 150.00,
      'invoiceID' => "c8acb91e080ad7bd8a2adc119c192885",
      'contactID' => $this->_contactId,
      'contributionID' => $contribution->id,
      'participantID' => $this->_participantId,
    );

    $this->ids['participant'] = $this->_participantId;
    $this->ids['event'] = $this->_eventId;
  }

  /**
   * Set up participant requirements for test.
   */
  public function _setUpPledgeObjects() {
    $this->_pledgeId = $this->pledgeCreate(array('contact_id' => $this->_contactId));
    //we'll create membership payment here because to make setup more re-usable
    $pledgePayment = $this->callAPISuccess('pledge_payment', 'create', array(
      'version' => 3,
      'pledge_id' => $this->_pledgeId,
      'contribution_id' => $this->_contributionId,
      'status_id' => 1,
      'actual_amount' => 50,
    ));

    $this->input = array(
      'component' => 'contribute',
      'total_amount' => 150.00,
      'invoiceID' => "c8acb91e080ad7bd8a2adc119c192885",
      'contactID' => $this->_contactId,
      'contributionID' => $this->_contributionId,
      'pledgeID' => $this->_pledgeId,
    );

    $this->ids['pledge_payment'][] = $pledgePayment['id'];
  }

}
