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

namespace Civi\Api4\Generic\Traits;

/**
 * Trait for Entities not intended to be publicly writable.
 */
trait ReadOnlyEntity {

  /**
   * Not intended to be used outside CiviCRM core code.
   *
   * @inheritDoc
   * @internal
   */
  public static function save($checkPermissions = TRUE) {
    return parent::save($checkPermissions);
  }

  /**
   * Not intended to be used outside CiviCRM core code.
   *
   * @inheritDoc
   * @internal
   */
  public static function create($checkPermissions = TRUE) {
    return parent::create($checkPermissions);
  }

  /**
   * Not intended to be used outside CiviCRM core code.
   *
   * @inheritDoc
   * @internal
   */
  public static function update($checkPermissions = TRUE) {
    return parent::update($checkPermissions);
  }

  /**
   * Not intended to be used outside CiviCRM core code.
   *
   * @inheritDoc
   * @internal
   */
  public static function delete($checkPermissions = TRUE) {
    return parent::delete($checkPermissions);
  }

  /**
   * Not intended to be used outside CiviCRM core code.
   *
   * @inheritDoc
   * @internal
   */
  public static function replace($checkPermissions = TRUE) {
    return parent::replace($checkPermissions);
  }

  /**
   * @return array
   */
  public static function permissions() {
    $permissions = parent::permissions();
    $permissions['create'] = $permissions['update'] = $permissions['delete'] = \CRM_Core_Permission::ALWAYS_DENY_PERMISSION;
    return $permissions;
  }

}
