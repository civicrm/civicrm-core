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
class SqlFunctionNULLIF extends SqlFunction {

  public $supportsExpansion = TRUE;

  protected static $category = self::CATEGORY_COMPARISON;

  protected static function params(): array {
    return [
      [
        'min_expr' => 2,
        'max_expr' => 2,
        'optional' => FALSE,
        'label' => ts('Compare with'),
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Unequal');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('The first value, only if it is not equal to the second.');
  }

}
