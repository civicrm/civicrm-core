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
        'optional' => FALSE,
        'must_be' => ['SqlEquation', 'SqlField'],
        'label' => ts('If'),
      ],
      [
        'optional' => FALSE,
        'must_be' => ['SqlField', 'SqlString', 'SqlNumber', 'SqlNull'],
        'label' => ts('Then'),
      ],
      [
        'optional' => FALSE,
        'must_be' => ['SqlField', 'SqlString', 'SqlNumber', 'SqlNull'],
        'label' => ts('Else'),
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
