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
 * (Read-only) Records tagged with one or more tags.
 *
 * Spans both `EntityTag`-backed entities (real DB tables) and any entity registered
 * via the `alterNonDbTaggableEntities` hook (tag-able but not a physical table, e.g. Afform).
 *
 * @searchable primary
 * @since 6.19
 * @package Civi\Api4
 */
class TaggedEntity extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\TaggedEntity\Get
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\TaggedEntity\Get(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction(__CLASS__, __FUNCTION__, function() {
      return Action\TaggedEntity\Get::fields();
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * @return array
   */
  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['access CiviCRM'],
    ];
  }

}
