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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace api\v4\Action;

use api\v4\Api4TestBase;
use Civi\Api4\Membership;
use Civi\Core\Event\GenericHookEvent;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class GetActionsTest extends Api4TestBase implements HookInterface, TransactionalInterface {

  /**
   * Listens for civi.api4.authorize event to manually permit any user to use membership.get api
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public function on_civi_api_authorize(GenericHookEvent $e): void {
    $apiRequest = $e->getApiRequest();
    if ($apiRequest['version'] == 4 && $apiRequest->getEntityName() === 'Membership' && $apiRequest->getActionName() === 'get') {
      $e->authorize();
      $e->stopPropagation();
    }
  }

  public function testActionPermissionsOverride(): void {
    $contact = $this->createTestRecord('Contact', [
      'first_name' => 'GetActions',
      'last_name' => 'testContact',
      'contact_type' => 'Individual',
    ]);
    $membershipType = $this->createTestRecord('MembershipType', [
      'label' => 'Student',
    ]);
    $this->createTestRecord('Membership', [
      'contact_id' => $contact['id'],
      'membership_type_id' => $membershipType['id'],
      'start_date' => date('Y-m-d'),
      'join_date' => date('Y-m-d'),
    ]);
    $this->createLoggedInUser();
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $actions = Membership::getActions()->setSelect(['name'])->execute()->column('name');
    $this->assertTrue(in_array('get', $actions));
    $this->assertFalse(in_array('create', $actions));
  }

}
