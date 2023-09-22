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

namespace Civi\Api4\Action\Entity;

/**
 * Get the names & docblocks of all APIv4 entities.
 *
 * Scans for api entities in core & enabled extensions.
 *
 * Also includes pseudo-entities from multi-record custom groups.
 */
class Get extends \Civi\Api4\Generic\BasicGetAction {

  /**
   * @var bool
   * @deprecated
   */
  protected $includeCustom;

  /**
   * Returns all APIv4 entities from core & enabled extensions.
   */
  protected function getRecords() {
    $provider = \Civi::service('action_object_provider');
    return array_filter($provider->getEntities(), function($entity) {
      // Check custom group permissions
      if ($this->checkPermissions && in_array('CustomValue', $entity['type']) && !\CRM_Core_Permission::customGroupAdmin()) {
        // Hack to get the id from the "view" url. If that url changes tests should catch it and we'll think of a better way to get that id
        $params = [];
        parse_str(parse_url($entity['paths']['view'])['query'], $params);
        $gid = $params['gid'];
        return in_array($gid, \CRM_Core_Permission::customGroup());
      }
      return TRUE;
    });
  }

}
