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
 * @group headless
 */
class CRM_Contribute_Form_ContributionViewTest extends CiviUnitTestCase {

  /**
   * Test that can still view a contribution without full permissions.
   */
  public function testContributionViewLimitedPermissions() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'access all custom data',
      'edit all contacts',
      'access CiviContribute',
      'edit contributions',
      'delete in CiviContribute',
    ];
    $contact_id = $this->individualCreate();
    $contribution = $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $contact_id,
      'financial_type_id' => 'Donation',
      'total_amount' => '10',
    ]);

    $_SERVER['REQUEST_URI'] = "civicrm/contact/view/contribution?reset=1&action=view&id={$contribution['id']}&cid={$contact_id}";
    $_GET['q'] = $_REQUEST['q'] = 'civicrm/contact/view/contribution';
    $_GET['reset'] = $_REQUEST['reset'] = 1;
    $_GET['action'] = $_REQUEST['action'] = 'view';
    $_GET['id'] = $_REQUEST['id'] = $contribution['id'];
    $_GET['cid'] = $_REQUEST['cid'] = $contact_id;

    $item = CRM_Core_Invoke::getItem(['civicrm/contact/view/contribution']);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    $contents = ob_get_clean();

    unset($_GET['q'], $_REQUEST['q']);
    unset($_GET['reset'], $_REQUEST['reset']);
    unset($_GET['action'], $_REQUEST['action']);
    unset($_GET['id'], $_REQUEST['id']);
    unset($_GET['cid'], $_REQUEST['cid']);

    $this->assertRegExp('/Contribution Total:\s+\$10\.00/', $contents);
    $this->assertStringContainsString('Mr. Anthony Anderson II', $contents);
  }

}
