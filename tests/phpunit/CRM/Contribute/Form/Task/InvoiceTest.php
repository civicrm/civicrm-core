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
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    CRM_Utils_Hook::singleton()->reset();
  }

  /**
   * CRM-17815 - Test due date and payment advice block in generated
   * invoice pdf for pending and completed contributions
   */
  public function testInvoiceForDueDate() {
    $contactIds = array();
    $params = array(
      'output' => 'pdf_invoice',
      'forPage' => 1,
    );

    $this->_individualId = $this->individualCreate();
    $contributionParams = array(
      'contact_id' => $this->_individualId,
      'total_amount' => 100,
      'financial_type_id' => 'Donation',
    );
    $result = $this->callAPISuccess('Contribution', 'create', $contributionParams);

    $contributionParams['contribution_status_id'] = 2;
    $contributionParams['is_pay_later'] = 1;
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);

    $contribution3 = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    $this->callAPISuccess('Payment', 'create', array('total_amount' => 8, 'contribution_id' => $contribution3['id']));

    $this->callAPISuccess('Contribution', 'create', array('id' => $contribution3['id'], 'is_pay_later' => 0));

    $contributionIDs = array(
      array($result['id']),
      array($contribution['id']),
      array($contribution3['id']),
    );

    $contactIds[] = $this->_individualId;
    foreach ($contributionIDs as $contributionID) {
      $invoiceHTML[current($contributionID)] = CRM_Contribute_Form_Task_Invoice::printPDF($contributionID, $params, $contactIds);
    }

    $this->assertNotContains('Due Date', $invoiceHTML[$result['id']]);
    $this->assertNotContains('PAYMENT ADVICE', $invoiceHTML[$result['id']]);

    $this->assertContains('Due Date', $invoiceHTML[$contribution['id']]);
    $this->assertContains('PAYMENT ADVICE', $invoiceHTML[$contribution['id']]);

    $this->assertContains('AMOUNT DUE: </font></b></td>
                  <td style = "padding-left:34px;text-align:right;"><b><font size = "1">$ 92.00</font></b></td>', $invoiceHTML[$contribution3['id']]);

  }

}
