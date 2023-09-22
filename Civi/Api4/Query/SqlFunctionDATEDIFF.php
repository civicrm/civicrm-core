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
class SqlFunctionDATEDIFF extends SqlFunction {

  protected static $category = self::CATEGORY_DATE;

  protected static $dataType = 'Integer';

  protected static function params(): array {
    return [
      [
        'max_expr' => 2,
        'min_expr' => 2,
        'optional' => FALSE,
        'label' => ts('diff'),
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Days between two dates');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('Number of days between two dates.');
  }

}
