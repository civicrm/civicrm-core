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

namespace Civi\API\Subscriber;

use Civi\API\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * For any API requests that correspond to a Doctrine entity
 * ($apiRequest['doctrineClass']), check permissions specified in
 * Civi\API\Annotation\Permission.
 */
class PermissionCheck implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'civi.api.authorize' => [
        ['onApiAuthorize', Events::W_LATE],
      ],
    ];
  }

  /**
   * @param \Civi\API\Event\AuthorizeEvent $event
   *   API authorization event.
   *
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function onApiAuthorize(\Civi\API\Event\AuthorizeEvent $event) {
    $apiRequest = $event->getApiRequest();
    if ($apiRequest['version'] < 4) {
      // return early unless we’re told explicitly to do the permission check
      if (empty($apiRequest['params']['check_permissions']) or $apiRequest['params']['check_permissions'] == FALSE) {
        $event->authorize();
        $event->stopPropagation();
        return;
      }

      require_once 'CRM/Core/DAO/permissions.php';
      $permissions = _civicrm_api3_permissions($apiRequest['entity'], $apiRequest['action'], $apiRequest['params']);

      // $params might’ve been reset by the alterAPIPermissions() hook
      if (isset($apiRequest['params']['check_permissions']) and $apiRequest['params']['check_permissions'] == FALSE) {
        $event->authorize();
        $event->stopPropagation();
        return;
      }

      if (!\CRM_Core_Permission::check($permissions) and !self::checkACLPermission($apiRequest)) {
        if (is_array($permissions)) {
          foreach ($permissions as &$permission) {
            if (is_array($permission)) {
              $permission = '( ' . implode(' or ', $permission) . ' )';
            }
          }
          $permissions = implode(' and ', $permissions);
        }
        // FIXME: Generating the exception ourselves allows for detailed error
        // but doesn't play well with multiple authz subscribers.
        throw new \Civi\API\Exception\UnauthorizedException("API permission check failed for {$apiRequest['entity']}/{$apiRequest['action']} call; insufficient permission: require $permissions");
      }

      $event->authorize();
      $event->stopPropagation();
    }
    elseif ($apiRequest['version'] == 4) {
      if (!$apiRequest->getCheckPermissions()) {
        $event->authorize();
        $event->stopPropagation();
      }
    }
  }

  /**
   * Check API for ACL permission.
   *
   * @param array $apiRequest
   *
   * @return bool
   */
  public function checkACLPermission($apiRequest) {
    switch ($apiRequest['entity']) {
      case 'UFGroup':
      case 'UFField':
        $ufGroups = \CRM_Core_PseudoConstant::get('CRM_Core_DAO_UFField', 'uf_group_id');
        $aclCreate = \CRM_ACL_API::group(\CRM_Core_Permission::CREATE, NULL, 'civicrm_uf_group', $ufGroups);
        $aclEdit = \CRM_ACL_API::group(\CRM_Core_Permission::EDIT, NULL, 'civicrm_uf_group', $ufGroups);
        $ufGroupId = $apiRequest['entity'] == 'UFGroup' ? $apiRequest['params']['id'] : $apiRequest['params']['uf_group_id'];
        if (in_array($ufGroupId, $aclEdit) or $aclCreate) {
          return TRUE;
        }
        break;

      //CRM-16777: Disable schedule reminder with ACLs.
      case 'ActionSchedule':
        $events = \CRM_Event_BAO_Event::getEvents();
        $aclEdit = \CRM_ACL_API::group(\CRM_Core_Permission::EDIT, NULL, 'civicrm_event', $events);
        $param = ['id' => $apiRequest['params']['id']];
        $eventId = \CRM_Core_BAO_ActionSchedule::retrieve($param, $value = []);
        if (in_array($eventId->entity_value, $aclEdit)) {
          return TRUE;
        }
        break;
    }

    return FALSE;
  }

}
