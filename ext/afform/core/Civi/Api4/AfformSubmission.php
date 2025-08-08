<?php
namespace Civi\Api4;

/**
 * AfformSubmission entity.
 *
 * Provided by the Afform: Core Runtime extension.
 *
 * @searchable secondary
 * @since 5.42
 * @package Civi\Api4
 */
class AfformSubmission extends Generic\DAOEntity {

  /**
   * @return array
   */
  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['manage own afform'],
    ];
  }

}
