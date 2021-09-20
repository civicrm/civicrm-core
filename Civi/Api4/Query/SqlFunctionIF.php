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
class SqlFunctionIF extends SqlFunction {

  protected static $category = self::CATEGORY_COMPARISON;

  protected static $dataType = 'String';

  protected static function params(): array {
    return [
      [
        'min_expr' => 3,
        'max_expr' => 3,
        'optional' => FALSE,
        'must_be' => ['SqlEquation', 'SqlField', 'SqlFunction', 'SqlString', 'SqlNumber', 'SqlNull'],
        'ui_defaults' => [
          ['type' => 'SqlField', 'placeholder' => ts('If')],
          ['type' => 'SqlField', 'placeholder' => ts('Then')],
          ['type' => 'SqlField', 'placeholder' => ts('Else')],
        ],
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('If/Else');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('If the field is empty, the first value, otherwise the second.');
  }

}
