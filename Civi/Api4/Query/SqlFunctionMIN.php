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
class SqlFunctionMIN extends SqlFunction {

  public $supportsExpansion = TRUE;

  protected static $category = self::CATEGORY_AGGREGATE;

  protected static function params(): array {
    return [
      [
        'flag_before' => ['' => NULL, 'DISTINCT' => ts('Distinct')],
        'must_be' => ['SqlField'],
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Min');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('The smallest value in the grouping.');
  }

}
