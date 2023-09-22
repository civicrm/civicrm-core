<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class SequentialcreditnotesTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use \Civi\Test\Api3TestTrait;

  /**
   * Setup for headless test.
   *
   * @return \Civi\Test\CiviEnvBuilder
   *
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless(): \Civi\Test\CiviEnvBuilder {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * Check credit note id creation
   * when a contribution is cancelled or refunded
   * createCreditNoteId();
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateCreditNoteId(): void {
    $this->_apiversion = 4;
    $contactId = $this->callAPISuccess('Contact', 'create', ['contact_type' => 'Individual', 'email' => 'b@example.com'])['id'];

    $params = [
      'contact_id' => $contactId,
      'currency' => 'USD',
      'financial_type_id' => 1,
      'contribution_status_id' => 3,
      'payment_instrument_id' => 1,
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
      'sequential' => TRUE,
    ];

    $creditNoteId = sequentialcreditnotes_create_credit_note_id();
    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];
    $this->assertEquals($contactId, $contribution['contact_id'], 'Check for contact id  creation.');
    $this->assertEquals($creditNoteId, $contribution['creditnote_id'], 'Check if credit note id is created correctly.');

    $params['id'] = $contribution['id'];
    $this->callAPISuccess('Contribution', 'create', $params);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $params['id']]);
    $this->assertEquals($creditNoteId, $contribution['creditnote_id'], 'Check if credit note id was not altered.');
  }

}
