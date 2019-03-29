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
 *  Test CRM_Contribute_Form_Contribution_ThankYou
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class CRM_Contribute_Form_Contribution_ThankYouTest extends CiviUnitTestCase {

  /**
   * Clean up DB.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
  }

  /**
   * Test that correct contribution status is fetched for both live and test contributions.
   */
  public function testLiveAndTestContributionStatus() {
    $paymentProcessorID = $this->paymentProcessorCreate(array('payment_processor_type_id' => 'Dummy'));

    $form = $this->getThankYouFormWithContribution($paymentProcessorID, FALSE, FALSE);
    $form->buildQuickForm();
    $isPendingOutcome = $form->get_template_vars('isPendingOutcome');

    $this->assertEquals(FALSE, $isPendingOutcome, 'Outcome should not be pending.');

    $form = $this->getThankYouFormWithContribution($paymentProcessorID, TRUE, FALSE);
    $form->buildQuickForm();
    $isPendingOutcome = $form->get_template_vars('isPendingOutcome');

    $this->assertEquals(TRUE, $isPendingOutcome, 'Outcome should be pending.');

    $form = $this->getThankYouFormWithContribution($paymentProcessorID, FALSE, TRUE);
    $form->buildQuickForm();
    $isPendingOutcome = $form->get_template_vars('isPendingOutcome');

    $this->assertEquals(FALSE, $isPendingOutcome, 'Outcome should not be pending.');

    $form = $this->getThankYouFormWithContribution($paymentProcessorID, TRUE, TRUE);
    $form->buildQuickForm();
    $isPendingOutcome = $form->get_template_vars('isPendingOutcome');

    $this->assertEquals(TRUE, $isPendingOutcome, 'Outcome should be pending.');
  }

  /**
   * Get CRM_Contribute_Form_Contribution_ThankYou form with attached contribution.
   *
   * @param $paymentProcessorID
   * @param bool $withPendingContribution
   * @param bool $isTestContribution
   * @return CRM_Contribute_Form_Contribution_ThankYou
   */
  private function getThankYouFormWithContribution($paymentProcessorID, $withPendingContribution = FALSE, $isTestContribution = FALSE) {
    $pageContribution = $this->getPageContribution((($withPendingContribution) ? 2 : 1), $isTestContribution);
    $form = $this->getThankYouForm();
    $form->_lineItem = array();

    $form->_params['contributionID'] = $pageContribution['contribution_id'];
    $form->_params['invoiceID'] = $pageContribution['invoice_id'];
    $form->_params['payment_processor_id'] = $paymentProcessorID;
    if ($isTestContribution) {
      $form->_mode = 'test';
    }

    return $form;
  }

  /**
   * Get Contribution and Invoice ID.
   *
   * @param $contributionStatus
   * @param bool $isTest
   * @return array
   */
  private function getPageContribution($contributionStatus, $isTest = FALSE) {
    $individualId = $this->individualCreate();
    $invoiceId = rand(100000, 999999);

    $contributionId = $this->contributionCreate(array(
      'contact_id'             => $individualId,
      'invoice_id'             => $invoiceId,
      'contribution_status_id' => $contributionStatus,
      'is_test'                => ($isTest) ? 1 : 0,
    ));

    return array(
      'contribution_id' => $contributionId,
      'invoice_id'      => $invoiceId,
    );
  }

  /**
   * Get CRM_Contribute_Form_Contribution_ThankYou Form
   *
   * @return CRM_Contribute_Form_Contribution_ThankYou
   */
  private function getThankYouForm() {
    $form = new CRM_Contribute_Form_Contribution_ThankYou();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $form->controller = new CRM_Contribute_Controller_Contribution();
    return $form;
  }

}
