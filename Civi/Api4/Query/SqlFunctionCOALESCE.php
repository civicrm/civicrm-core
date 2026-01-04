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
class SqlFunctionCOALESCE extends SqlFunction {

  protected static $category = self::CATEGORY_COMPARISON;

  protected static $dataType = 'String';

  protected static function params(): array {
    return [
      [
        'max_expr' => 99,
        'optional' => FALSE,
        'label' => ts('Value?'),
        'can_be_empty' => TRUE,
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Coalesce');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('The first value that is not null.');
  }

}
