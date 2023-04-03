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
 * Methods of handling JWTs
 *
 * @searchable none
 * @since 5.62
 * @package Authx
 */
class JWT extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\JWT\create
   */
  public static function create($checkPermissions = TRUE) {
    return (new Action\JWT\Create(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Action\JWT\validate
   */
  public static function validate($checkPermissions = TRUE) {
    return (new Action\JWT\Validate(__CLASS__, __FUNCTION__))
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
      'create' => ['generate JWT'],
      'validate' => [],
    ];
  }

}
