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
class SqlFunctionIFNULL extends SqlFunction {

  protected static $category = self::CATEGORY_COMPARISON;

  protected static function params(): array {
    return [
      [
        'optional' => FALSE,
        'must_be' => ['SqlEquation', 'SqlField', 'SqlFunction'],
        'label' => ts('Primary Value'),
      ],
      [
        'optional' => FALSE,
        'must_be' => ['SqlField', 'SqlFunction', 'SqlString', 'SqlNumber', 'SqlNull'],
        'label' => ts('Fallback value'),
        'can_be_empty' => TRUE,
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('If Null');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('The primary value, or if it is null, the fallback value.');
  }

}
