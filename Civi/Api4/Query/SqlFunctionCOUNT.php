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
class SqlFunctionCOUNT extends SqlFunction {

  protected static $category = self::CATEGORY_AGGREGATE;

  protected static $dataType = 'Integer';

  protected static function params(): array {
    return [
      [
        'flag_before' => ['' => NULL, 'DISTINCT' => ts('Distinct')],
        'max_expr' => 1,
        'must_be' => ['SqlField', 'SqlWild'],
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Count');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('The number of items in the grouping.');
  }

}
