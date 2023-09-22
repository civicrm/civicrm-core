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
class SqlFunctionMONTH extends SqlFunction {

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
    return ts('Month only');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('The numeric month (1-12) of a date.');
  }

  /**
   * @return array
   */
  public static function getOptions(): ?array {
    return [
      1 => ts('January'),
      2 => ts('February'),
      3 => ts('March'),
      4 => ts('April'),
      5 => ts('May'),
      6 => ts('June'),
      7 => ts('July'),
      8 => ts('August'),
      9 => ts('September'),
      10 => ts('October'),
      11 => ts('November'),
      12 => ts('December'),
    ];
  }

}
