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
 * Base class for ad-hoc entities that implement CRUD actions.
 *
 * This is one of 3 possible base classes for an APIv4 Entity
 * (the others are `DAOEntity` and `AbstractEntity`).
 *
 * This base class is for entities that do not have an associated DAO but do implement CRUD actions.
 * It can be used in one of these ways:
 *
 * 1. Extend this class and define the static variables `$getter`, `$setter` & `$deleter` with callbacks to handle CRUD operations.
 *    In that case there is no need to implement any actions other than `getFields`.
 * 2. Override the `get`, `create`, `delete`, etc. methods with custom BasicAction implementations.
 * 3. Some combination of the above two options, e.g. defining a callback for `$getter` and using the default `get` action,
 *    but leaving `$deleter` unset and overriding the `delete` method with a custom BasicBatchAction to handle deletion.
 *
 * Note: the `replace` action does not require any callback as it internally calls the entity's `get`, `save` and `delete` actions.
 */
abstract class BasicEntity extends AbstractEntity {

  /**
   * Function to read records. Used by `get` action.
   *
   * @var callable
   *   Function(BasicGetAction $thisAction): array[]
   *
   * This function should return an array of records, and is passed a copy of the BasicGetAction object as its first argument.
   * The simplest implementation is for it to return every record and the BasicGetAction automatically handle sorting and filtering.
   *
   * If performance is a concern, it can take advantage of some helper functions for e.g. fetching item(s) by id.
   * @see BasicGetAction::getRecords()
   */
  protected static $getter;

  /**
   * Function to write a record. Used by `create`, `update` and `save`.
   *
   * @var callable
   *   Function(array $item, BasicCreateAction|BasicSaveAction|BasicUpdateAction $thisAction): array
   *
   * This function is called once per write. It takes a single record as the first param, and a reference to
   * the action object as the second.
   *
   * This callback should check the $idField of the record to determine whether the operation is a create or update.
   *
   * It should return the updated record as an array.
   */
  protected static $setter;

  /**
   * Function to delete records. Used by `delete` action.
   *
   * @var callable
   *   Function(array $item, BasicBatchAction $thisAction): array
   *
   * This function is called once per delete. It takes a single record as the first param, and a reference to
   * the action object as the second.
   *
   * This callback should check the $idField of the item to determine which record to delete.
   *
   * It should return the deleted record as an array.
   */
  protected static $deleter;

  /**
   * @param bool $checkPermissions
   * @return BasicGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new BasicGetAction(static::getEntityName(), __FUNCTION__, static::$getter))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return BasicCreateAction
   */
  public static function create($checkPermissions = TRUE) {
    return (new BasicCreateAction(static::getEntityName(), __FUNCTION__, static::$setter))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return BasicSaveAction
   */
  public static function save($checkPermissions = TRUE) {
    return (new BasicSaveAction(static::getEntityName(), __FUNCTION__, static::$setter))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return BasicUpdateAction
   */
  public static function update($checkPermissions = TRUE) {
    return (new BasicUpdateAction(static::getEntityName(), __FUNCTION__, static::$setter))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return BasicBatchAction
   */
  public static function delete($checkPermissions = TRUE) {
    return (new BasicBatchAction(static::getEntityName(), __FUNCTION__, static::$deleter))
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

  /**
   * @inheritDoc
   */
  public static function getInfo() {
    $info = parent::getInfo();
    if (isset(static::$idField)) {
      // Deprecated in favor of `@primaryKey` annotation
      $info['primary_key'] = (array) static::$idField;
    }
    return $info;
  }

}
