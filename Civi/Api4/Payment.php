<?php

namespace Civi\Api4;

/**
 * Payment abstract entity API.
 *
 * @searchable none
 * @since 5.69
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

}
