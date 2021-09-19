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
class SqlFunctionROUND extends SqlFunction {

  protected static $category = self::CATEGORY_MATH;

  protected static function params(): array {
    return [
      [
        'optional' => FALSE,
        'min_expr' => 1,
        'max_expr' => 2,
        'must_be' => ['SqlNumber', 'SqlField'],
        'ui_defaults' => [
          ['type' => 'SqlField', 'placeholder' => ts('Number')],
          ['type' => 'SqlNumber', 'placeholder' => ts('Decimals')],
        ],
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Round');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('Number rounded to specified number of decimal places.');
  }

}
