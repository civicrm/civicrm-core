<?php
namespace Civi\Api4;

/**
 * Data segmentation sets for searches.
 *
 * @package Civi\Api4
 */
class SearchSegment extends Generic\DAOEntity {
  use \Civi\Api4\Generic\Traits\ManagedEntity;

  public static function permissions() {
    $permissions = parent::permissions();
    $permissions['default'] = [['administer CiviCRM data', 'administer search_kit']];
    return $permissions;
  }

}
