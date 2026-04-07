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
class SqlFunctionEXTRACT extends SqlFunction {

  protected static $category = self::CATEGORY_PARTIAL_DATE;

  protected static function params(): array {
    return [
      [
        'label' => ts('Unit'),
        'flag_before' => self::getDateIntervals(),
        'max_expr' => 0,
        'optional' => FALSE,
      ],
      [
        'name' => 'FROM',
        'must_be' => ['SqlField', 'SqlString', 'SqlFunction'],
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Partial Date');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('Extract part(s) of a date (e.g. the day, year, etc.)');
  }

}
