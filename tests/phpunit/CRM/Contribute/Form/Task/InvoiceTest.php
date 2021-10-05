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
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class CRM_Contribute_Form_Task_InvoiceTest extends CiviUnitTestCase {

  protected $_individualId;

  /**
   * Clean up after each test.
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->revertTemplateToReservedTemplate('contribution_invoice_receipt');
    CRM_Utils_Hook::singleton()->reset();
    parent::tearDown();
  }

  /**
   * CRM-17815 - Test due date and payment advice block in generated
   * invoice pdf for pending and completed contributions
   */
  public function testInvoiceForDueDate() {
    $contactIds = [];
    $params = [
      'output' => 'pdf_invoice',
      'forPage' => 1,
    ];

    $this->_individualId = $this->individualCreate();
    $contributionParams = [
      'contact_id' => $this->_individualId,
      'total_amount' => 100,
      'financial_type_id' => 'Donation',
    ];
    $result = $this->callAPISuccess('Contribution', 'create', $contributionParams);

    $contributionParams['contribution_status_id'] = 2;
    $contributionParams['is_pay_later'] = 1;
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);

    $contribution3 = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    $this->callAPISuccess('Payment', 'create', ['total_amount' => 8, 'contribution_id' => $contribution3['id']]);

    $this->callAPISuccess('Contribution', 'create', ['id' => $contribution3['id'], 'is_pay_later' => 0]);

    $contributionIDs = [
      [$result['id']],
      [$contribution['id']],
      [$contribution3['id']],
    ];

    $contactIds[] = $this->_individualId;
    foreach ($contributionIDs as $contributionID) {
      $invoiceHTML[current($contributionID)] = CRM_Contribute_Form_Task_Invoice::printPDF($contributionID, $params, $contactIds);
    }

    $this->assertStringNotContainsString('Due Date', $invoiceHTML[$result['id']]);
    $this->assertStringNotContainsString('PAYMENT ADVICE', $invoiceHTML[$result['id']]);
    $this->assertStringContainsString('Mr. Anthony Anderson II', $invoiceHTML[$result['id']]);

    $this->assertStringContainsString('Due Date', $invoiceHTML[$contribution['id']]);
    $this->assertStringContainsString('PAYMENT ADVICE', $invoiceHTML[$contribution['id']]);

    $this->assertStringContainsString('AMOUNT DUE:</font></b></td>
                <td style="text-align:right;"><b><font size="1">$ 92.00</font></b></td>', $invoiceHTML[$contribution3['id']]);
  }

  /**
   * PR 13477 - Fix incorrect display of Line Items created via API
   * when printing invoice (for Participants).
   *
   * @throws \CRM_Core_Exception
   */
  public function testInvoiceForLineItems() {

    $this->enableTaxAndInvoicing();

    $event = $this->eventCreatePaid([]);

    $individualOneId = $this->individualCreate();
    $individualTwoId = $this->individualCreate();
    $contactIds = [$individualOneId, $individualTwoId];

    $priceSetId = CRM_Price_BAO_PriceSet::getFor('civicrm_event', $event['id']);
    $priceField = $this->callAPISuccess('PriceField', 'get', ['price_set_id' => $priceSetId]);
    $priceFieldValues = $this->callAPISuccess('PriceFieldValue', 'get', [
      'sequential' => 1,
      'price_field_id' => $priceField['id'],
    ]);

    $lineItemParams = [];
    foreach ($priceFieldValues['values'] as $key => $priceFieldValue) {
      $lineItemParams[] = [
        'line_item' => [
          $priceFieldValue['id'] => [
            'price_field_id' => $priceField['id'],
            'label' => $priceFieldValue['label'],
            'financial_type_id' => $priceFieldValue['financial_type_id'],
            'price_field_value_id' => $priceFieldValue['id'],
            'qty' => 1,
            'field_title' => $priceFieldValue['label'],
            'unit_price' => $priceFieldValue['amount'],
            'line_total' => $priceFieldValue['amount'],
            'entity_table' => 'civicrm_participant',
          ],
        ],
        // participant params
        'params' => [
          'contact_id' => $contactIds[$key],
          'event_id' => $event['id'],
          'status_id' => 1,
          'price_set_id' => $priceSetId,
          'participant_fee_amount' => $priceFieldValue['amount'],
          'participant_fee_level' => $priceFieldValue['label'],
        ],
      ];
    }

    $orderParams = [
      'contact_id' => $individualOneId,
      'total_amount' => array_reduce($priceFieldValues['values'], function($total, $priceFieldValue) {
        $total += $priceFieldValue['amount'];
        return $total;
      }),
      'financial_type_id' => $priceFieldValues['values'][0]['financial_type_id'],
      'currency' => 'USD',
      'line_items' => $lineItemParams,
    ];

    $order = $this->callAPISuccess('Order', 'create', $orderParams);

    $pdfParams = [
      'output' => 'pdf_invoice',
      'forPage' => 1,
    ];

    $invoiceHTML = CRM_Contribute_Form_Task_Invoice::printPDF([$order['id']], $pdfParams, [$individualOneId]);

    $lineItems = $this->callAPISuccess('LineItem', 'get', ['contribution_id' => $order['id']]);

    foreach ($lineItems['values'] as $lineItem) {
      $this->assertStringContainsString("<font size=\"1\">$ {$lineItem['line_total']}</font>", $invoiceHTML);
    }

    $totalAmount = $this->formatMoneyInput($order['values'][$order['id']]['total_amount']);
    $this->assertStringContainsString("TOTAL USD</font></b></td>
                <td style=\"text-align:right;\"><font size=\"1\">$ $totalAmount</font>", $invoiceHTML);

  }

  /**
   * Test invoices if payment is made with different currency.
   *
   * https://lab.civicrm.org/dev/core/issues/2269
   *
   * @throws \CRM_Core_Exception
   */
  public function testThatInvoiceShowTheActuallContributionCurrencyInsteadOfTheDefaultOne() {
    $this->setDefaultCurrency('USD');

    $this->_individualId = $this->individualCreate();

    $contributionID = $this->setupContribution();

    $params = [
      'output' => 'pdf_invoice',
      'forPage' => 1,
    ];

    $invoiceHTML = CRM_Contribute_Form_Task_Invoice::printPDF([$contributionID], $params, [$this->_individualId]);

    $this->assertStringNotContainsString('$', $invoiceHTML);
    $this->assertStringNotContainsString('Amount USD', $invoiceHTML);
    $this->assertStringNotContainsString('TOTAL USD', $invoiceHTML);
    $this->assertStringContainsString('£ 0.00', $invoiceHTML);
    $this->assertStringContainsString('£ 100.00', $invoiceHTML);
    $this->assertStringContainsString('Amount GBP', $invoiceHTML);
    $this->assertStringContainsString('TOTAL GBP', $invoiceHTML);

  }

  /**
   * Test invoice contact fields.
   */
  public function testInvoiceContactFields(): void {
    $this->swapMessageTemplateForTestTemplate('contribution_invoice_receipt');
    $contactID = $this->individualCreate([
      'postal_code' => 2345,
      'street_address' => 'on my street',
      'supplemental_address_1' => 'and more detail',
      'supplemental_address_2' => 'and more',
      'stateProvinceAbbreviation' => 'ME',
      'city' => 'Baltimore',
      'country' => 'US',
      'external_identifier' => 2345,
    ]);
    $params = [
      'output' => 'pdf_invoice',
      'forPage' => 1,
    ];
    $invoiceHTML = CRM_Contribute_Form_Task_Invoice::printPDF([$this->setupContribution(['contact_id' => $contactID])], $params, [$contactID]);
    $expected = [
      'externalIdentifier (token):::2345',
      'displayName (token):::Mr. Anthony Anderson II',
    ];
    foreach ($expected as $string) {
      $this->assertStringContainsString($string, $invoiceHTML);
    }
  }

  /**
   * Set up a contribution.
   *
   * @param array $params
   *
   * @return int
   */
  protected function setupContribution(array $params = []): int {
    $contributionParams = array_merge([
      'contact_id' => $this->_individualId,
      'total_amount' => 100,
      'currency' => 'GBP',
      'financial_type_id' => 'Donation',
      'contribution_status_id' => 1,
    ], $params);
    return (int) $this->callAPISuccess('Contribution', 'create', $contributionParams)['id'];
  }

}
