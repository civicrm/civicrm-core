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
class SqlFunctionCONCAT_WS extends SqlFunction {

  protected static $category = self::CATEGORY_STRING;

  protected static $dataType = 'String';

  protected static function params(): array {
    return [
      [
        'optional' => FALSE,
        'must_be' => ['SqlString'],
        'label' => ts('Separator'),
      ],
      [
        'max_expr' => 99,
        'optional' => FALSE,
        'must_be' => ['SqlField', 'SqlString'],
        'label' => ts('Plus'),
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Combine text');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('Every non-null value joined by a separator.');
  }

}
