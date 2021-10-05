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
class SqlFunctionLEAST extends SqlFunction {

  public $supportsExpansion = TRUE;

  protected static $category = self::CATEGORY_COMPARISON;

  protected static function params(): array {
    return [
      [
        'max_expr' => 99,
        'min_expr' => 2,
        'optional' => FALSE,
        'label' => ts('Else'),
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Least');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('The smallest of all provided values.');
  }

}
