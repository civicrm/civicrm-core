<?php
namespace Civi\Api4;

/**
 * Data segmentation sets for searches.
 *
 * @since 5.50
 * @package Civi\Api4
 */
class SearchSegment extends Generic\DAOEntity {
  use \Civi\Api4\Generic\Traits\ManagedEntity;

  public static function permissions() {
    $permissions = parent::permissions();
    $permissions['default'] = ['manage own search_kit'];
    return $permissions;
  }

}
