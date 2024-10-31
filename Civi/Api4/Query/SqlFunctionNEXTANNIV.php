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
class SqlFunctionNEXTANNIV extends SqlFunction {

  protected static $category = self::CATEGORY_DATE;

  protected static $dataType = 'Date';

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
    return ts('Next Anniversary');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('Date of next anniversary of this date.');
  }

  /**
   * @inheritDoc
   */
  public static function renderExpression(string $output): string {
    return \CRM_Utils_Date::getAnniversarySql($output);
  }

}
