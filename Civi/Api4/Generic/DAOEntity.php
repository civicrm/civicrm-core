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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 * $Id$
 *
 */


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
   * @return DAOSaveAction
   */
  public static function save() {
    return new DAOSaveAction(static::class, __FUNCTION__);
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
