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

namespace Civi\Core;

use Civi\Core\Event\GenericHookEvent;

/**
 * Blocks access to non-bypass users while CiviCRM is in maintenance mode.
 */
class MaintenanceMode {

  /**
   * When in maintenance mode, block non-bypass users from accessing pages.
   *
   * @param array $args
   */
  public static function check(array $args): void {
    if (!\CRM_Utils_System::isMaintenanceMode()) {
      return;
    }
    $path = implode('/', $args);
    // The API4 AJAX handler has its own maintenance-mode gate (CRM_Api4_Page_AJAX::run());
    // other civicrm/ajax/* pages (legacy CRM_*_Page_AJAX classes, REST) have none.
    if (($args[1] ?? NULL) === 'ajax' && ($args[2] ?? NULL) === 'api4') {
      return;
    }
    // The login page loads its Angular bundle from the asset builder.
    if ($path === 'civicrm/asset/builder') {
      return;
    }

    $maintenanceMessage = ts('Site is under maintenance. Please check back shortly.');

    if (\CRM_Core_Permission::check([['administer CiviCRM system', 'cms:bypass maintenance mode']])) {
      // Administrators get the same text as statusCheck(), so the two collapse into one message.
      $coreMaintenanceMode = \Civi::settings()->get('core_maintenance_mode');
      $isExplicit = $coreMaintenanceMode && $coreMaintenanceMode !== 'inherit';
      $notice = ($isExplicit && \CRM_Core_Permission::check('administer CiviCRM')) ? self::getAdminWarning() : $maintenanceMessage;
      \CRM_Core_Session::setStatus($notice, ts('Maintenance Mode'), 'warning');
      return;
    }

    // Auth paths remain accessible, as do routes where an anonymous user would be sent to the login form.
    $loginPaths = [
      'civicrm/login',
      'civicrm/login/password',
      'civicrm/logout',
      'civicrm/mfa/totp',
      'civicrm/mfa/totp-setup',
    ];
    $blocked = !in_array($path, $loginPaths) && !self::isDeniedToAnonymous($args);

    // Listeners may set $event->blocked to allow or block a route.
    $event = GenericHookEvent::create(['args' => $args, 'path' => $path, 'blocked' => $blocked]);
    \Civi::dispatcher()->dispatch('civi.maintenance.access', $event);

    if (!$event->blocked) {
      \CRM_Core_Session::setStatus($maintenanceMessage, ts('Maintenance Mode'), 'warning');
      return;
    }

    http_response_code(503);
    \CRM_Utils_System::setHttpHeader('Retry-After', '300');

    if (!empty($_REQUEST['snippet'])) {
      \CRM_Utils_System::sendJSONResponse([
        'status_code' => 503,
        'status_message' => $maintenanceMessage,
      ], 503);
      \CRM_Utils_System::civiExit();
    }

    $content = '<h2>' . ts('Maintenance Mode') . '</h2><p>' . htmlspecialchars($maintenanceMessage) . '</p>';
    if (\CRM_Core_Session::getLoggedInContactID()) {
      $content .= '<p><a href="' . \CRM_Utils_System::url('civicrm/logout') . '">' . ts('Log out') . '</a></p>';
    }

    \CRM_Core_Config::singleton()->userSystem->renderMaintenanceMessage($content);
    \CRM_Utils_System::civiExit();
  }

  /**
   * @return string
   */
  public static function getAdminWarning(): string {
    return ts('CiviCRM is currently in maintenance mode. To deactivate, update the <code>core_maintenance_mode</code> setting.');
  }

  /**
   * Would an anonymous user be denied access to this route?
   *
   * The normal flow then sends them to the login form, so there is nothing to protect.
   *
   * @param array $args
   * @return bool
   */
  protected static function isDeniedToAnonymous(array $args): bool {
    if (\CRM_Core_Session::getLoggedInContactID()) {
      return FALSE;
    }
    $item = \CRM_Core_Invoke::getItem($args);
    return $item && !\CRM_Core_Permission::checkMenuItem($item);
  }

}
