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
   * Test links render correctly for manual processor.
   *
   * @throws \API_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testLinks() {
    $contactID = $this->individualCreate();
    $recurID = ContributionRecur::create()->setValues([
      'contact_id' => $contactID,
      'amount' => 10,
      'frequency_interval' => 'week',
      'start_date' => 'now',
      'is_active' => TRUE,
      'contribution_status_id:name' => 'Pending',
    ])
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

    $templateVariable = CRM_Core_Smarty::singleton()->get_template_vars();
    $this->assertEquals('Mr. Anthony Anderson II', $templateVariable['displayName']);
    $this->assertEquals("<span><a href=\"/index.php?q=civicrm/contact/view/contributionrecur&amp;reset=1&amp;id=" . $recurID . "&amp;cid=" . $contactID . "&amp;context=contribution\" class=\"action-item crm-hover-button\" title='View Recurring Payment' >View</a><a href=\"/index.php?q=civicrm/contribute/updaterecur&amp;reset=1&amp;action=update&amp;crid=1&amp;cid=3&amp;context=contribution\" class=\"action-item crm-hover-button\" title='Edit Recurring Payment' >Edit</a><a href=\"/index.php?q=civicrm/contribute/unsubscribe&amp;reset=1&amp;crid=" . $recurID . "&amp;cid=" . $contactID . "&amp;context=contribution\" class=\"action-item crm-hover-button\" title='Cancel' >Cancel</a></span>",
      $templateVariable['activeRecurRows'][1]['action']
    );
  }

}
