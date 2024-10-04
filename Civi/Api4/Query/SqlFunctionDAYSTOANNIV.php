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
class SqlFunctionDAYSTOANNIV extends SqlFunction {

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
    return ts('Days to Anniversary');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('Number of days until the next anniversary of this date.');
  }

  /**
   * @inheritDoc
   */
  public static function renderExpression(string $output): string {
    $anniversarySql = \CRM_Utils_Date::getAnniversarySql($output);
    return "DATEDIFF($anniversarySql, CURDATE())";
  }

}
