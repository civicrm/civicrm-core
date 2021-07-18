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

  protected static $params = [
    [
      'min_expr' => 3,
      'max_expr' => 3,
      'optional' => FALSE,
    ],
  ];

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('If');
  }

  /**
   * Prevent formatting based on first field
   *
   * @see \Civi\Api4\Utils\FormattingUtil::formatOutputValues
   * @param string $value
   * @param string $dataType
   * @return string|array
   */
  public function formatOutputValue($value, &$dataType) {
    $dataType = NULL;
    return $value;
  }

}
