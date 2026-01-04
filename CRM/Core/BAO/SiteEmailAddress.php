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

/**
 * SiteEmailAddress business logic
 */
class CRM_Core_BAO_SiteEmailAddress extends CRM_Core_DAO_SiteEmailAddress implements \Civi\Core\HookInterface {

  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    // When setting default, ensure other records for this domain are not the default
    if (($event->action === 'create' || $event->action === 'edit') && !empty($event->params['is_default'])) {
      $domainId = $event->params['domain_id'] ?? self::getDbVal('domain_id', $event->id);
      CRM_Core_DAO::executeQuery('UPDATE civicrm_site_email_address SET is_default = 0 WHERE domain_id = %1 AND is_default = 1', [
        1 => [$domainId, 'Positive'],
      ]);
    }
  }

}
