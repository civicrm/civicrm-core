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

namespace Civi\Api4\Generic;

/**
 * Base class for DAO-based entities.
 */
abstract class DAOEntity extends AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return DAOGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new DAOGetAction(static::class, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return DAOSaveAction
   */
  public static function save($checkPermissions = TRUE) {
    return (new DAOSaveAction(static::class, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return DAOGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new DAOGetFieldsAction(static::class, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return DAOCreateAction
   */
  public static function create($checkPermissions = TRUE) {
    return (new DAOCreateAction(static::class, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return DAOUpdateAction
   */
  public static function update($checkPermissions = TRUE) {
    return (new DAOUpdateAction(static::class, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return DAODeleteAction
   */
  public static function delete($checkPermissions = TRUE) {
    return (new DAODeleteAction(static::class, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return BasicReplaceAction
   */
  public static function replace($checkPermissions = TRUE) {
    return (new BasicReplaceAction(static::class, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return string
   */
  protected static function getEntityTitle() {
    $name = static::getEntityName();
    $dao = \CRM_Core_DAO_AllCoreTables::getFullName($name);
    return $dao ? $dao::getEntityTitle() : $name;
  }

  /**
   * @return array
   */
  public static function getInfo() {
    $info = parent::getInfo();
    $dao = \CRM_Core_DAO_AllCoreTables::getFullName($info['name']);
    if ($dao) {
      $info['icon'] = $dao::$_icon;
      $info['dao'] = $dao;
    }
    return $info;
  }

}
