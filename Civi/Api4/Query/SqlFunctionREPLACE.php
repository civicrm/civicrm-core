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
class SqlFunctionREPLACE extends SqlFunction {

  protected static $category = self::CATEGORY_STRING;

  protected static function params(): array {
    return [
      [
        'min_expr' => 3,
        'max_expr' => 3,
        'optional' => FALSE,
        'must_be' => ['SqlString', 'SqlField'],
        'ui_defaults' => [
          ['type' => 'SqlField', 'placeholder' => ts('Source')],
          ['type' => 'SqlString', 'placeholder' => ts('Find')],
          ['type' => 'SqlString', 'placeholder' => ts('Replace')],
        ],
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('Replace text');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('Substitutes one value for another in the text.');
  }

}
