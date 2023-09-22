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
 * CiviCRM settings api.
 *
 * Used to read/write persistent setting data from CiviCRM.
 *
 * @see \Civi\Core\SettingsBag
 * @searchable none
 * @since 5.19
 * @package Civi\Api4
 */
class Setting extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\Setting\Get
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\Setting\Get(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Setting\Set
   */
  public static function set($checkPermissions = TRUE) {
    return (new Action\Setting\Set(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Setting\Revert
   */
  public static function revert($checkPermissions = TRUE) {
    return (new Action\Setting\Revert(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\Setting\GetFields
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Action\Setting\GetFields(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
