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
class SqlFunctionBINARY extends SqlFunction {

  protected static $category = self::CATEGORY_STRING;

  protected static $dataType = 'String';

  protected static function params(): array {
    return [
      [
        'optional' => FALSE,
        'must_be' => ['SqlField', 'SqlString'],
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Binary');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('Case-sensitive string treatment.');
  }

}
