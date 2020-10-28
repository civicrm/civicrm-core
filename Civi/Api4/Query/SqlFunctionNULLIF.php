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

  protected static $params = [
    [
      'expr' => 1,
      'optional' => FALSE,
    ],
    [
      'expr' => 1,
      'optional' => FALSE,
    ],
  ];

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Null if');
  }

}
