<?php

namespace Civi\Api4\Generic;

/**
 * Base class for DAO-based entities.
 */
abstract class DAOEntity extends AbstractEntity {

  /**
   * @return DAOGetAction
   */
  public static function get() {
    return new DAOGetAction(static::class, __FUNCTION__);
  }

  /**
   * @return DAOGetFieldsAction
   */
  public static function getFields() {
    return new DAOGetFieldsAction(static::class, __FUNCTION__);
  }

  /**
   * @return DAOCreateAction
   */
  public static function create() {
    return new DAOCreateAction(static::class, __FUNCTION__);
  }

  /**
   * @return DAOUpdateAction
   */
  public static function update() {
    return new DAOUpdateAction(static::class, __FUNCTION__);
  }

  /**
   * @return DAODeleteAction
   */
  public static function delete() {
    return new DAODeleteAction(static::class, __FUNCTION__);
  }

  /**
   * @return BasicReplaceAction
   */
  public static function replace() {
    return new BasicReplaceAction(static::class, __FUNCTION__);
  }

}
