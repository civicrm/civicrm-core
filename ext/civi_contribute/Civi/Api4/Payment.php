<?php

namespace Civi\Api4;

/**
 * Payment abstract entity API.
 *
 * @searchable none
 * @since 5.75
 * @package Civi\Api4
 */
class Payment extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction(__CLASS__, __FUNCTION__, function() {
      return [];
    }))->setCheckPermissions($checkPermissions);
  }

  public static function create($checkPermissions = TRUE) {
    return (new Action\Payment\Create(__CLASS__, __FUNCTION__))->setCheckPermissions($checkPermissions);
  }

  public static function get($checkPermissions = TRUE) {
    return (new Action\Payment\Get(__CLASS__, __FUNCTION__))->setCheckPermissions($checkPermissions);
  }

}
