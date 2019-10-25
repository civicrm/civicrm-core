<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
   * @return DAOGetAction
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
