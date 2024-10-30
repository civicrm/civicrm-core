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

namespace Civi\BAO;

use CRM_Core_DAO;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class SK_Entity extends CRM_Core_DAO {

  /**
   * This is the primary key - it has an underscore to prevent possible conflicts with other columns.
   *
   * @var int
   */
  protected $_row;

  /**
   * Primary key field.
   *
   * @var string[]
   */
  public static $_primaryKey = ['_row'];

  /**
   * Over-ride the parent to prevent a NULL return.
   *
   * @return array
   */
  public static function &fields(): array {
    $result = [];
    return $result;
  }

  /**
   * @return bool
   */
  public static function tableHasBeenAdded(): bool {
    return TRUE;
  }

}
