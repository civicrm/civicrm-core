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
 * Class CRM_Contribute_Form_Task_StatusTest
 */
class CRM_Contribute_Form_Task_StatusTest extends CiviUnitTestCase {

  protected $_individualId;

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    CRM_Utils_Hook::singleton()->reset();
  }

  /**
   * Test update pending contribution
   */
  public function testUpdatePendingContribution() {
    $this->_individualId = $this->individualCreate();
    $form = new CRM_Contribute_Form_Task_Status();

    // create a pending contribution
    $contributionParams = array(
      'contact_id' => $this->_individualId,
      'total_amount' => 100,
      'financial_type_id' => 'Donation',
      'contribution_status_id' => 2,
    );
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    $contributionId = $contribution['id'];
    $form->setContributionIds(array($contributionId));

    $form->buildQuickForm();

    $params = array(
      "contribution_status_id" => 1,
      "trxn_id_{$contributionId}" => NULL,
      "check_number_{$contributionId}" => NULL,
      "fee_amount_{$contributionId}" => 0,
      "trxn_date_{$contributionId}" => date('m/d/Y'),
      "payment_instrument_id_{$contributionId}" => 4,
    );

    CRM_Contribute_Form_Task_Status::processForm($form, $params);

    $contribution = $this->callAPISuccess('Contribution', 'get', array('id' => $contributionId));
    $updatedContribution = $contribution['values'][1];

    $this->assertEquals('', $updatedContribution['contribution_source']);
    $this->assertEquals(date("Y-m-d"), date("Y-m-d", strtotime($updatedContribution['receive_date'])));
    $this->assertNotEquals("00:00:00", date("H:i:s", strtotime($updatedContribution['receive_date'])));
    $this->assertEquals('Completed', $updatedContribution['contribution_status']);
  }

}
