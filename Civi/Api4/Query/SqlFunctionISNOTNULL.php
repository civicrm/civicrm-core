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
class SqlFunctionISNOTNULL extends SqlFunction {

  protected static $category = self::CATEGORY_COMPARISON;

  protected static $dataType = 'Boolean';

  protected static function params(): array {
    return [
      [
        'optional' => FALSE,
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Is not null');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('FALSE if the value is NULL, otherwise TRUE.');
  }

  /**
   * Render the final expression
   *
   * @param string $output
   * @return string
   */
  public static function renderExpression(string $output): string {
    return "!ISNULL($output)";
  }

}
