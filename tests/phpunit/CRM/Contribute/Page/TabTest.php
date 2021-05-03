<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | Use of this source code is governed by the AGPL license with some  |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use Civi\Api4\Contribution;
use Civi\Api4\ContributionRecur;

/**
 * Class CRM_Contribute_Page_AjaxTest
 * @group headless
 */
class CRM_Contribute_Page_TabTest extends CiviUnitTestCase {

  /**
   * Clean up after test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Test links render correctly for manual processor.
   *
   * @throws \API_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testLinksManual(): void {
    [$contactID, $recurID] = $this->setupTemplate();

    $templateVariable = CRM_Core_Smarty::singleton()->get_template_vars();
    $this->assertEquals('Mr. Anthony Anderson II', $templateVariable['displayName']);
    $this->assertEquals("<span><a href=\"/index.php?q=civicrm/contact/view/contributionrecur&amp;reset=1&amp;id=" . $recurID . "&amp;cid=" . $contactID . "&amp;context=contribution\" class=\"action-item crm-hover-button\" title='View Recurring Payment' >View</a><a href=\"/index.php?q=civicrm/contribute/updaterecur&amp;reset=1&amp;action=update&amp;crid=1&amp;cid=3&amp;context=contribution\" class=\"action-item crm-hover-button\" title='Edit Recurring Payment' >Edit</a><a href=\"/index.php?q=civicrm/contribute/unsubscribe&amp;reset=1&amp;crid=" . $recurID . "&amp;cid=" . $contactID . "&amp;context=contribution\" class=\"action-item crm-hover-button\" title='Cancel' >Cancel</a></span>",
      $this->getActionHtml()
    );
  }

  /**
   * Test links render correctly for manual processor.
   *
   * @throws \API_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testLinksPaypalStandard(): void {
    $this->setupTemplate([
      'payment_processor_id' => $this->paymentProcessorCreate(['payment_processor_type_id' => 'PayPal_Standard']),
      'contact_id' => $this->createLoggedInUser(),
    ]);
    $expected = '<span><a href="/index.php?q=civicrm/contact/view/contributionrecur&amp;reset=1&amp;id=1&amp;cid=3&amp;context=contribution" class="action-item crm-hover-button" title=\'View Recurring Payment\' >View</a><a href="/index.php?q=civicrm/contribute/updaterecur&amp;reset=1&amp;action=update&amp;crid=1&amp;cid=3&amp;context=contribution" class="action-item crm-hover-button" title=\'Edit Recurring Payment\' >Edit</a></span><span class=\'btn-slide crm-hover-button\'>more<ul class=\'panel\'><li><a href="/index.php?q=civicrm/contribute/unsubscribe&amp;reset=1&amp;crid=1&amp;cid=3&amp;context=contribution" class="action-item crm-hover-button" title=\'Cancel\' >Cancel</a></li><li><a href="/index.php?q=civicrm/contribute/updatebilling&amp;reset=1&amp;crid=1&amp;cid=3&amp;context=contribution" class="action-item crm-hover-button" title=\'Change Billing Details\' >Change Billing Details</a></li></ul></span>';
    $this->assertEquals($expected, $this->getActionHtml());

    $page = new CRM_Contribute_Page_UserDashboard();
    $page->run();
    $expected = '<span><a href="https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_subscr-find&alias=sunil._1183377782_biz_api1.webaccess.co.in" class="action-item crm-hover-button no-popup" title=\'Cancel\' >Cancel</a>'
      . '<a href="/index.php?q=civicrm/contact/view/contributionrecur&amp;reset=1&amp;id=1&amp;cid=3&amp;context=dashboard" class="action-item crm-hover-button" title=\'View Recurring Payment\' >View</a>'
      . '</span><span class=\'btn-slide crm-hover-button\'>more<ul class=\'panel\'><li><a href="/index.php?q=civicrm/contribute/updaterecur&amp;reset=1&amp;action=update&amp;crid=1&amp;cid=3&amp;context=dashboard" class="action-item crm-hover-button" title=\'Edit Recurring Payment\' >Edit</a></li><li><a href="/index.php?q=civicrm/contribute/updatebilling&amp;reset=1&amp;crid=1&amp;cid=3&amp;context=dashboard" class="action-item crm-hover-button" title=\'Change Billing Details\' >Change Billing Details</a></li></ul></span>';
    $this->assertEquals(
      $expected,
      $this->getDashboardActionHtml()
    );
  }

  /**
   * Set up template for user dashboard.
   *
   * Create the recurring contribution, contribution and run the dashboard.
   *
   * @param array $recurParams
   *
   * @return array
   * @throws \API_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function setupTemplate($recurParams = []): array {
    $contactID = $recurParams['contact_id'] ?? $this->individualCreate();
    $recurID = ContributionRecur::create()->setValues(array_merge([
      'contact_id' => $contactID,
      'amount' => 10,
      'frequency_interval' => 'week',
      'start_date' => 'now',
      'is_active' => TRUE,
      'contribution_status_id:name' => 'Pending',
    ], $recurParams))
      ->addChain(
        'contribution',
        Contribution::create()->setValues([
          'contribution_id' => '$id',
          'financial_type_id:name' => 'Donation',
          'total_amount' => 60,
          'receive_date' => 'now',
          'contact_id' => $contactID,
        ])
      )->execute()->first()['id'];
    $page = new CRM_Contribute_Page_Tab();
    $page->_contactId = $contactID;
    $page->_action = CRM_Core_Action::VIEW;
    $page->browse();
    return [$contactID, $recurID];
  }

  /**
   * Get the html assigned as actions.
   *
   * @return string
   */
  protected function getActionHtml(): string {
    return CRM_Core_Smarty::singleton()
      ->get_template_vars()['activeRecurRows'][1]['action'];
  }

  /**
   * Get the html assigned as actions.
   *
   * @return string
   */
  protected function getDashboardActionHtml(): string {
    return CRM_Core_Smarty::singleton()
      ->get_template_vars()['recurRows'][1]['action'];
  }

}
