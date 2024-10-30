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
class SqlFunctionDAYOFWEEK extends SqlFunction {

  protected static $category = self::CATEGORY_DATE;

  protected static $dataType = 'Integer';

  protected static function params(): array {
    return [
      [
        'max_expr' => 1,
        'optional' => FALSE,
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Day of Week');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('The day of the week of a date.');
  }

  /**
   * @return array
   */
  public static function getOptions(): ?array {
    return [
      1 => ts('Sunday'),
      2 => ts('Monday'),
      3 => ts('Tuesday'),
      4 => ts('Wednesday'),
      5 => ts('Thursday'),
      6 => ts('Friday'),
      7 => ts('Saturday'),
    ];
  }

}
