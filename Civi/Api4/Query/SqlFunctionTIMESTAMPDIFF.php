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
class SqlFunctionTIMESTAMPDIFF extends SqlFunction {

  protected static $category = self::CATEGORY_DATE;

  protected static $dataType = 'Integer';

  protected static function params(): array {
    return [
      [
        'label' => ts('Unit'),
        'flag_before' => [
          'MICROSECOND' => ts('Microseconds'),
          'SECOND' => ts('Seconds'),
          'MINUTE' => ts('Minutes'),
          'HOUR' => ts('Hours'),
          'DAY' => ts('Days'),
          'WEEK' => ts('Weeks'),
          'MONTH' => ts('Months'),
          'QUARTER' => ts('Quarters'),
          'YEAR' => ts('Years'),
        ],
        'max_expr' => 0,
        'optional' => FALSE,
      ],
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
    return ts('Time between two dates');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('Number of minutes, hours, days, etc. between two dates.');
  }

}
