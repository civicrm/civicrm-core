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
class CRM_Core_InvokeTest extends CiviUnitTestCase {

  /**
   * Test that no php errors come up invoking dashboard url for non-admins
   * Motivation: This currently fails on php 7.4 because of IDS and magicquotes.
   */
  public function testInvokeDashboardForNonAdmin(): void {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];

    $_SERVER['REQUEST_URI'] = 'civicrm/dashboard?reset=1';
    $_GET['q'] = 'civicrm/dashboard';

    $item = CRM_Core_Invoke::getItem(['civicrm/dashboard?reset=1']);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    ob_end_clean();
  }

  /**
   * Test dashboard with something actually on it.
   */
  public function testInvokeDashboardWithGettingStartedDashlet(): void {
    $user_id = $this->createLoggedInUser();
    $this->callAPISuccess('DashboardContact', 'create', [
      'dashboard_id' => 2,
      'contact_id' => $user_id,
    ]);

    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];

    $_SERVER['REQUEST_URI'] = 'civicrm/dashboard?reset=1';
    $_GET['q'] = 'civicrm/dashboard';

    $item = CRM_Core_Invoke::getItem(['civicrm/dashboard?reset=1']);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    ob_end_clean();
  }

}
