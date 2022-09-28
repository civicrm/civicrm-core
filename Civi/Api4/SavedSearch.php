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
namespace Civi\Api4;

/**
 * SavedSearch entity.
 *
 * Stores search criteria for smart groups and SearchKit displays.
 *
 * @see https://docs.civicrm.org/user/en/latest/the-user-interface/search-kit/
 * @see https://docs.civicrm.org/user/en/latest/organising-your-data/smart-groups/
 * @searchable secondary
 * @since 5.24
 * @package Civi\Api4
 */
class SavedSearch extends Generic\DAOEntity {

  use Generic\Traits\ManagedEntity;

  public static function permissions() {
    $permissions = parent::permissions();
    $permissions['get'] = ['access CiviCRM'];
    return $permissions;
  }

}
