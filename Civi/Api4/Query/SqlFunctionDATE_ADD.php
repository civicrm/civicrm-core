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
class SqlFunctionDATE_ADD extends SqlFunction {

  protected static $category = self::CATEGORY_DATE;

  protected static $dataType = 'Date';

  protected static function params(): array {
    return [
      [
        'optional' => FALSE,
        'must_be' => ['SqlField'],
      ],
      [
        'label' => ts('Interval to add'),
        'must_be' => ['SqlNumber'],
        'flag_before' => ['INTERVAL' => ts('Add')],
        'flag_after' => self::getDateIntervals(),
        'optional' => FALSE,
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Date Addition');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('Adds a time/date interval to a date.');
  }

}
