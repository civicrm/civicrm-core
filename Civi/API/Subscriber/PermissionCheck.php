<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

namespace Civi\API\Subscriber;
use Civi\API\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * For any API requests that correspond to a Doctrine entity ($apiRequest['doctrineClass']), check
 * permissions specified in Civi\API\Annotation\Permission.
 */
class PermissionCheck implements EventSubscriberInterface {
  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return array(
      Events::AUTHORIZE => array(
        array('onApiAuthorize', Events::W_LATE),
      ),
    );
  }

  /**
   * @param \Civi\API\Event\AuthorizeEvent $event
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

      if (!\CRM_Core_Permission::check($permissions)) {
        if (is_array($permissions)) {
          $permissions = implode(' and ', $permissions);
        }
        // FIXME: Generating the exception ourselves allows for detailed error but doesn't play well with multiple authz subscribers.
        throw new \Civi\API\Exception\UnauthorizedException("API permission check failed for {$apiRequest['entity']}/{$apiRequest['action']} call; insufficient permission: require $permissions");
      }

      $event->authorize();
      $event->stopPropagation();
    }
  }
}
