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
 * Scans for api entities in core, enabled components & enabled extensions.
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
   * Returns all APIv4 entities from core, enabled components and enabled extensions.
   */
  protected function getRecords() {
    $provider = \Civi::service('action_object_provider');
    return array_filter($provider->getEntities(), function($entity) {
      // Only include DAO entities from enabled components
      $daoName = $entity['dao'] ?? NULL;
      return (!$daoName || $daoName::isComponentEnabled());
    });
  }

}
