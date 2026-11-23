<?php

use Civi\Api4\Contact;
use Civi\Api4\Contribution;
use Civi\Test\HeadlessInterface;
use Civi\Core\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Tests for credit note id generation on reversed contributions.
 *
 * @group headless
 */
class SequentialcreditnotesTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  /**
   * Setup for headless test.
   *
   * @return \Civi\Test\CiviEnvBuilder
   *
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless(): \Civi\Test\CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Check a credit note id is created when a contribution is cancelled,
   * and kept when the contribution is updated.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateCreditNoteId(): void {
    $contactID = Contact::create(FALSE)
      ->setValues(['contact_type' => 'Individual', 'email_primary.email' => 'b@example.com'])
      ->execute()->single()['id'];

    $values = [
      'contact_id' => $contactID,
      'currency' => 'USD',
      'financial_type_id:name' => 'Donation',
      'contribution_status_id:name' => 'Cancelled',
      'payment_instrument_id:name' => 'Credit Card',
      'source' => 'STUDENT',
      'receive_date' => '20080522000000',
      'receipt_date' => '20080522000000',
      'non_deductible_amount' => 0.00,
      'total_amount' => 300.00,
      'fee_amount' => 5,
      'net_amount' => 295,
      'trxn_id' => '76ereeswww835',
      'invoice_id' => '93ed39a9e9hd621bs0eafe3da82',
      'thankyou_date' => '20080522',
    ];

    $creditNoteID = sequentialcreditnotes_create_credit_note_id();
    $contribution = Contribution::create(FALSE)
      ->setValues($values)
      ->execute()->single();
    $this->assertSame($creditNoteID, $contribution['creditnote_id'], 'Check if credit note id is created correctly.');

    Contribution::update(FALSE)
      ->addWhere('id', '=', $contribution['id'])
      ->setValues($values)
      ->execute();
    $this->assertSame($creditNoteID, Contribution::get(FALSE)
      ->addWhere('id', '=', $contribution['id'])
      ->addSelect('creditnote_id')
      ->execute()->single()['creditnote_id'], 'Check if credit note id was not altered.');
  }

}
