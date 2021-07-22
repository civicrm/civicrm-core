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
class SqlFunctionISNULL extends SqlFunction {

  protected static $category = self::CATEGORY_COMPARISON;

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
    return ts('Is null');
  }

  /**
   * Reformat result as boolean
   *
   * @see \Civi\Api4\Utils\FormattingUtil::formatOutputValues
   * @param string $value
   * @param string $dataType
   * @return string|array
   */
  public function formatOutputValue($value, &$dataType) {
    // Value is always TRUE or FALSE
    $dataType = 'Boolean';
    return $value;
  }

}
