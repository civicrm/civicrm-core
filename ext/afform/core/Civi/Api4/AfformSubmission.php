<?php
namespace Civi\Api4;

/**
 * AfformSubmission entity.
 *
 * Provided by the Afform: Core Runtime extension.
 *
 * @searchable secondary
 * @package Civi\Api4
 */
class AfformSubmission extends Generic\DAOEntity {

  /**
   * @return array
   */
  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'default' => [['administer CiviCRM', 'administer afform']],
    ];
  }

}
