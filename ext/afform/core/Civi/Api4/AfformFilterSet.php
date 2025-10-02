<?php
namespace Civi\Api4;

/**
 * AfformFilterSet entity.
 *
 * Provided by the Afform: Core Runtime extension.
 *
 * @searchable secondary
 * @since 6.8
 * @package Civi\Api4
 */
class AfformFilterSet extends Generic\DAOEntity {

  /**
   * @return array
   */
  public static function permissions() {
    // TODO: require higher level permissions to edit
    // other people's filter sets
    return [
      'default' => ['access CiviCRM'],
    ];
  }

}
