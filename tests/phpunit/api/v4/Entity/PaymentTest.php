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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace api\v4\Entity;

use Civi\Api4\Contribution;
use Civi\Api4\Payment;

/**
 * @group headless
 */
class PaymentTest extends \CiviUnitTestCase {

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * A second Payment.create with a trxn_id already recorded for the contribution should be
   * idempotent - returning the existing payment rather than erroring or duplicating it. Covers
   * a payment processor webhook racing a synchronous confirmation of the same charge.
   */
  public function testCreatePaymentDuplicateTrxnIDIsIdempotent(): void {
    $contributionID = $this->contributionCreate([
      'contact_id' => $this->individualCreate(),
      'total_amount' => 100,
      'contribution_status_id' => 'Pending',
      'fee_amount' => 0,
    ]);

    $firstPayment = Payment::create(FALSE)
      ->addValue('contribution_id', $contributionID)
      ->addValue('total_amount', 100)
      ->addValue('fee_amount', 2.50)
      ->addValue('trxn_date', date('Y-m-d H:i:s'))
      ->addValue('trxn_id', 'ch_race_condition')
      ->execute()->single();

    $secondPayment = Payment::create(FALSE)
      ->addValue('contribution_id', $contributionID)
      ->addValue('total_amount', 100)
      ->addValue('fee_amount', 2.50)
      ->addValue('trxn_date', date('Y-m-d H:i:s'))
      ->addValue('trxn_id', 'ch_race_condition')
      ->execute()->single();

    $this->assertEquals($firstPayment['id'], $secondPayment['id'], 'The second call should return the payment the first call recorded, not create a new one.');

    $paymentCount = Payment::get(FALSE)
      ->addWhere('contribution_id', '=', $contributionID)
      ->selectRowCount()
      ->execute()
      ->count();
    $this->assertEquals(1, $paymentCount, 'Only one payment should have been recorded for the contribution.');

    $totalFeeAmount = array_sum(Payment::get(FALSE)
      ->addWhere('contribution_id', '=', $contributionID)
      ->addSelect('fee_amount')
      ->execute()
      ->column('fee_amount'));
    $this->assertEquals(2.50, $totalFeeAmount, 'The fee should be recorded once, not doubled by the duplicate call.');

    $contribution = Contribution::get(FALSE)
      ->addWhere('id', '=', $contributionID)
      ->addSelect('contribution_status_id:name')
      ->execute()->single();
    $this->assertEquals('Completed', $contribution['contribution_status_id:name']);
  }

  /**
   * A payment and its refund can share a trxn_id (some processors don't mint a distinct one for
   * a refund). A replayed payment call must still match itself, not the refund.
   */
  public function testCreatePaymentDuplicateTrxnIDDoesNotMatchRefundSharingIt(): void {
    $contributionID = $this->contributionCreate([
      'contact_id' => $this->individualCreate(),
      'total_amount' => 100,
      'contribution_status_id' => 'Pending',
      'fee_amount' => 0,
    ]);

    $payment = Payment::create(FALSE)
      ->addValue('contribution_id', $contributionID)
      ->addValue('total_amount', 100)
      ->addValue('trxn_id', 'ch_reused_for_refund')
      ->execute()->single();

    $refund = $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $contributionID,
      'total_amount' => -100,
      'trxn_id' => 'ch_reused_for_refund',
      'cancelled_payment_id' => $payment['id'],
    ]);

    // A webhook replay of the *original* payment call - it must find itself, not the refund.
    $paymentReplay = Payment::create(FALSE)
      ->addValue('contribution_id', $contributionID)
      ->addValue('total_amount', 100)
      ->addValue('trxn_id', 'ch_reused_for_refund')
      ->execute()->single();

    $this->assertEquals($payment['id'], $paymentReplay['id'], 'A replay of the original payment should match the payment, not the refund sharing its trxn_id.');
    $this->assertNotEquals($refund['id'], $paymentReplay['id']);

    $paymentCount = Payment::get(FALSE)
      ->addWhere('contribution_id', '=', $contributionID)
      ->selectRowCount()
      ->execute()
      ->count();
    $this->assertEquals(2, $paymentCount, 'The replay must not create a third row.');
  }

  /**
   * create() must hold the per-contribution lock while recording the payment and release it
   * afterwards.
   *
   * This asserts the locking is wired up, not the race itself - GET_LOCK() is per-connection, so
   * a single-connection test can't hold and contend for the lock at once (same limitation as
   * CRM_Core_MenuTest::testRebuildRunsUnderLock()). The hold is observed via the civicrm_post
   * hook on FinancialTrxn, which fires mid-completePayment().
   */
  public function testCreateRunsUnderLock(): void {
    $contributionID = $this->contributionCreate([
      'contact_id' => $this->individualCreate(),
      'total_amount' => 100,
      'contribution_status_id' => 'Pending',
      'fee_amount' => 0,
    ]);
    $isFree = fn() => (int) \Civi::lockManager()->create('data.contribute.paymentCreate.' . $contributionID)->isFree();

    $this->assertSame(1, $isFree(), 'the payment-create lock should be free before create()');

    $freeDuringCreate = NULL;
    \CRM_Utils_Hook::singleton()->setHook('civicrm_post', function ($op, $objectName, $objectId, &$objectRef) use ($isFree, &$freeDuringCreate) {
      if ($objectName === 'FinancialTrxn' && $op === 'create' && $freeDuringCreate === NULL) {
        $freeDuringCreate = $isFree();
      }
    });

    Payment::create(FALSE)
      ->addValue('contribution_id', $contributionID)
      ->addValue('total_amount', 100)
      ->execute();

    $this->assertSame(0, $freeDuringCreate, 'create() should hold the lock while recording the payment');
    $this->assertSame(1, $isFree(), 'create() should release the lock when finished');
  }

}
