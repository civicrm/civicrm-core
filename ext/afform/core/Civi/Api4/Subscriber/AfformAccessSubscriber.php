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

use Civi\Api4\Event\AuthorizeRecordEvent;
use Civi\Api4\Utils\AfformSaveTrait;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Preprocess api authorize requests
 * @service
 * @internal
 */
class AfformAccessSubscriber extends AutoService implements EventSubscriberInterface {
  use AfformSaveTrait;

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api4.authorizeRecord::Afform' => ['onApiAuthorizeRecord', 500],
    ];
  }

  /**
   * @param \Civi\Api4\Event\AuthorizeRecordEvent $event
   *   API record authorize event
   */
  public function onApiAuthorizeRecord(AuthorizeRecordEvent $event) {
    $apiRequest = $event->getApiRequest();
    $action = $apiRequest->getActionName();

    if (!in_array($action, ['revert', 'delete', 'update'], TRUE)) {
      // We only care about these actions.
      return;
    }

    /** @var \CRM_Afform_AfformScanner $scanner */
    $scanner = \Civi::service('afform_scanner');
    $afform = $event->getRecord();

    // Get user as we need to check for Test user
    $user_id = \CRM_Core_Session::getLoggedInContactID();
    if ('update' === $action) {
      $orig = [];
      $this->checkNameForAfform($afform, $orig, $scanner);
      // Check if updating or creating
      if ($orig) {
        // We have an existing afform, so we need to check `manage own afform` permissions
        if (!$this->checkAccess($apiRequest, $afform, $user_id)) {
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
