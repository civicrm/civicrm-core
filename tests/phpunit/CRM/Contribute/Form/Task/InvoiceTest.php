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

use Civi\Api4\Address;
use Civi\Api4\Domain;

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
  public function testInvoiceForDueDate(): void {
    $contactIds = [];
    $params = [
      'output' => 'pdf_invoice',
      'forPage' => 1,
    ];

    $contactID = $this->individualCreate();
    $this->callAPISuccess('Address', 'create', [
      'contact_id' => $contactID,
      'street_address' => '9 Downing Street',
      'state_province_id' => 'Maine',
      'supplemental_address_1' => 'Back Alley',
      'supplemental_address_2' => 'Left corner',
      'postal_code' => 90990,
      'city' => 'Auckland',
      'country_id' => 'US',
    ]);
    $contributionParams = [
      'contact_id' => $contactID,
      'total_amount' => 100,
      'financial_type_id' => 'Donation',
      'source' => 'Donor gift',
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
    $this->assertStringContainsString('Left corner ME', $invoiceHTML[$result['id']]);
    $this->assertStringContainsString('9 Downing Street Back Alley', $invoiceHTML[$result['id']]);
    $this->assertStringContainsString('Auckland  90990', $invoiceHTML[$result['id']]);
    $this->assertStringContainsString('United States', $invoiceHTML[$result['id']]);
    $this->assertStringContainsString('Donor gift', $invoiceHTML[$result['id']]);

    $this->assertStringContainsString('Due Date', $invoiceHTML[$contribution['id']]);
    $this->assertStringContainsString('PAYMENT ADVICE', $invoiceHTML[$contribution['id']]);

    $this->assertStringContainsString('AMOUNT DUE:</font></b></td>
        <td style="text-align:right;"><b><font size="1">$92.00</font></b></td>', $invoiceHTML[$contribution3['id']]);
  }

  /**
   * PR 13477 - Fix incorrect display of Line Items created via API
   * when printing invoice (for Participants).
   *
   * @throws \CRM_Core_Exception
   */
  public function testInvoiceForLineItems(): void {

    $this->enableTaxAndInvoicing();

    $event = $this->eventCreatePaid([]);

    $individualOneID = $this->individualCreate();
    $individualTwoID = $this->individualCreate();

    $priceSetId = $this->ids['PriceSet']['PaidEvent'];
    $priceField = $this->callAPISuccess('PriceField', 'get', ['price_set_id' => $priceSetId]);
    $priceFieldValues = $this->callAPISuccess('PriceFieldValue', 'get', [
      'sequential' => 1,
      'price_field_id' => $priceField['id'],
    ]);

    $lineItemParams = [
      [
        'line_item' => [
          $this->ids['PriceField']['PaidEvent'] => [
            'price_field_id' => $priceField['id'],
            'price_field_value_id' => $this->ids['PriceFieldValue']['PaidEvent_standard'],
            'qty' => 1,
            'entity_table' => 'civicrm_participant',
          ],
        ],
        // participant params
        'params' => [
          'contact_id' => $individualOneID,
          'event_id' => $event['id'],
          'status_id' => 1,
        ],
      ],
      [
        'line_item' => [
          $this->ids['PriceField']['PaidEvent'] => [
            'price_field_id' => $priceField['id'],
            'price_field_value_id' => $this->ids['PriceFieldValue']['PaidEvent_student'],
            'qty' => 1,
            'entity_table' => 'civicrm_participant',
          ],
        ],
        // participant params
        'params' => [
          'contact_id' => $individualTwoID,
          'event_id' => $event['id'],
          'status_id' => 1,
        ],
      ],
    ];

    $orderParams = [
      'contact_id' => $individualOneID,
      'financial_type_id' => $priceFieldValues['values'][0]['financial_type_id'],
      'currency' => 'USD',
      'line_items' => $lineItemParams,
    ];

    $order = $this->callAPISuccess('Order', 'create', $orderParams);

    $pdfParams = [
      'output' => 'pdf_invoice',
      'forPage' => 1,
    ];

    $invoiceHTML = CRM_Contribute_Form_Task_Invoice::printPDF([$order['id']], $pdfParams, [$individualOneID]);

    $lineItems = $this->callAPISuccess('LineItem', 'get', ['contribution_id' => $order['id']]);

    foreach ($lineItems['values'] as $lineItem) {
      $this->assertStringContainsString("<font size=\"1\">$" . $lineItem['line_total'] . '</font>', $invoiceHTML);
    }

    $totalAmount = $this->formatMoneyInput($order['values'][$order['id']]['total_amount']);
    $this->assertStringContainsString("TOTAL USD</font></b></td>
        <td style=\"text-align:right;\"><font size=\"1\">$" . $totalAmount . '</font>', $invoiceHTML);

  }

  /**
   * Test invoices if payment is made with different currency.
   *
   * https://lab.civicrm.org/dev/core/issues/2269
   */
  public function testThatInvoiceShowsTheActualContributionCurrencyInsteadOfTheDefaultOne(): void {
    $this->setDefaultCurrency('USD');
    $contactID = Domain::get()->addSelect('contact_id')->execute()->first()['contact_id'];
    Address::create()->setValues(['contact_id' => $contactID, 'city' => 'Beverley Hills', 'state_province_id:label' => 'California', 'country_id:label' => 'United States', 'postal_code' => 90210])->execute();
    Civi::cache('metadata')->clear();
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
    $this->assertStringContainsString('£0.00', $invoiceHTML);
    $this->assertStringContainsString('£100.00', $invoiceHTML);
    $this->assertStringContainsString('Amount GBP', $invoiceHTML);
    $this->assertStringContainsString('TOTAL GBP', $invoiceHTML);
    $this->assertStringContainsString('California', $invoiceHTML);
    $this->assertStringContainsString('90210', $invoiceHTML);
    $this->assertStringContainsString('United States', $invoiceHTML);
    $this->assertStringContainsString('Default Domain Name', $invoiceHTML);
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
      'currency (token):::GBP',
      'currency (smarty):::GBP',
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
