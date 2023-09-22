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
 * Base class for DAO entities (sql tables).
 *
 * This is one of 3 possible base classes for an APIv4 Entity
 * (the others are `BasicEntity` and `AbstractEntity`).
 *
 * This base class is used for entities that have an associated DAO and support CRUD operations.
 *
 * Entities that extend this class can override actions and add others on an ad-hoc basis.
 *
 * DAO entities which do not support all CRUD operations should instead extend AbstractEntity
 * in order to implement just the actions appropriate to that entity.
 */
abstract class DAOEntity extends AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return DAOGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new DAOGetAction(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return DAOSaveAction
   */
  public static function save($checkPermissions = TRUE) {
    return (new DAOSaveAction(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return DAOGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new DAOGetFieldsAction(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return DAOCreateAction
   */
  public static function create($checkPermissions = TRUE) {
    return (new DAOCreateAction(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return DAOUpdateAction
   */
  public static function update($checkPermissions = TRUE) {
    return (new DAOUpdateAction(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return DAODeleteAction
   */
  public static function delete($checkPermissions = TRUE) {
    return (new DAODeleteAction(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return BasicReplaceAction
   */
  public static function replace($checkPermissions = TRUE) {
    return (new BasicReplaceAction(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return AutocompleteAction
   */
  public static function autocomplete($checkPermissions = TRUE) {
    return (new AutocompleteAction(static::getEntityName(), __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

}
