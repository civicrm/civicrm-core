<?php
namespace Civi\Api4;

/**
 * AfformSubmissionData entity.
 *
 * Provided by the Afform: Core Runtime extension.
 *
 * @searchable secondary
 * @package Civi\Api4
 */
class AfformSubmissionData extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\AfformSubmissionData\Get
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\AfformSubmissionData\Get(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\AfformSubmissionData\GetFields
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Action\AfformSubmissionData\GetFields(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

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
