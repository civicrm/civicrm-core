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
 * Methods of handling (JWT) authx credentialss
 *
 * @searchable none
 * @since 5.62
 * @package Authx
 */
class AuthxCredential extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\AuthxCredential\create
   */
  public static function create($checkPermissions = TRUE) {
    return (new Action\AuthxCredential\Create(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\AuthxCredential\validate
   */
  public static function validate($checkPermissions = TRUE) {
    return (new Action\AuthxCredential\Validate(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction(__CLASS__, __FUNCTION__, function() {
      return [];
    }))->setCheckPermissions($checkPermissions);
  }

  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['administer CiviCRM'],
      'create' => ['generate any authx credential'],
      'validate' => ['validate any authx credential'],
    ];
  }

}
