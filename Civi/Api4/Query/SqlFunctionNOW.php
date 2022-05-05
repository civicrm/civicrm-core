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

namespace Civi\Api4\Query;

/**
 * Sql function
 */
class SqlFunctionNOW extends SqlFunction {

  protected static $category = self::CATEGORY_DATE;

  protected static $dataType = 'Timestamp';

  protected static function params(): array {
    return [];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Now');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('The current date and time.');
  }

}
