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

namespace Civi\Api4\Subscriber;

use Civi\Core\Attribute\CiviEventListener;
use Civi\Api4\Event\AuthorizeRecordEvent;
use Civi\Api4\Utils\AfformSaveTrait;

/**
 * Preprocess api authorize requests
 */
class AfformAccessSubscriber {
  use AfformSaveTrait;

  /**
   * @param \Civi\Api4\Event\AuthorizeRecordEvent $event
   *   API record authorize event
   */
  #[CiviEventListener(event: 'civi.api4.authorizeRecord::Afform', priority: 500)]
  public function onApiAuthorizeRecord(AuthorizeRecordEvent $event) {
    $apiRequest = $event->getApiRequest();
    $action = $apiRequest->getActionName();

    if (!in_array($action, ['revert', 'delete', 'update', 'create'], TRUE)) {
      // We only care about these actions.
      return;
    }

    /** @var \CRM_Afform_AfformScanner $scanner */
    $scanner = \Civi::service('afform_scanner');
    $afform = $event->getRecord();

    // Get user as we need to check for Test user
    $user_id = \CRM_Core_Session::getLoggedInContactID();
    if (in_array($action, ['update', 'create'], TRUE)) {
      $orig = [];
      $this->checkNameForAfform($afform, $orig, $scanner);
      // Check if updating or creating
      if ($orig) {
        // We have an existing afform, so we need to check `manage own afform` permissions.
        // Its owner is the stored one: a submitted created_id is the value being written, and must
        // pass as well, so an owner cannot hand the form to someone else.
        $stored = ['created_id' => $orig['created_id'] ?? NULL] + $afform;
        if (!$this->checkAccess($apiRequest, $stored, $user_id)
          || (array_key_exists('created_id', $afform) && !$this->checkAccess($apiRequest, $afform, $user_id))) {
          $event->setAuthorized(FALSE);
        }
      }
    }
    else {
      if (!$this->checkAccess($apiRequest, $afform, $user_id)) {
        $event->setAuthorized(FALSE);
      }
    }

  }

  /**
   * Checks for access based on permissions and created_id if exists.
   *
   * @param array|\Civi\Api4\Generic\AbstractAction $apiRequest The current request.
   * @param array $afform The current afform entity for the request.
   * @param int $user_id The current user id.
   *
   * @return bool
   */
  protected function checkAccess($apiRequest, $afform, $user_id) {
    // Check permissions to Revert
    if ($apiRequest->getCheckPermissions() && !\CRM_Core_Permission::check('administer afform') && \CRM_Core_Permission::check('manage own afform') && (empty($afform['created_id']) || $afform['created_id'] !== $user_id)) {
      return FALSE;
    }
    return TRUE;
  }

}
