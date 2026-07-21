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

  public $supportsExpansion = TRUE;

  protected static $category = self::CATEGORY_COMPARISON;

  protected static function params(): array {
    return [
      [
        'optional' => FALSE,
        'must_be' => ['SqlEquation', 'SqlField', 'SqlFunction'],
        'label' => ts('If'),
      ],
      [
        'optional' => FALSE,
        'must_be' => ['SqlField', 'SqlString', 'SqlNumber', 'SqlNull', 'SqlFunction'],
        'label' => ts('Then'),
        'can_be_empty' => TRUE,
      ],
      [
        'optional' => FALSE,
        'must_be' => ['SqlField', 'SqlString', 'SqlNumber', 'SqlNull', 'SqlFunction'],
        'label' => ts('Else'),
        'can_be_empty' => TRUE,
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
    return ts('If the field is boolean TRUE, or any number except 0, or a string starting with the digits 1-9, the first value; otherwise the second.');
  }

  public function getFields(): array {
    $fields = [];
    foreach ([$this->args[1] ?? NULL, $this->args[2] ?? NULL, $this->args[0] ?? NULL] as $arg) {
      if (!empty($arg['expr'])) {
        foreach ($arg['expr'] as $expr) {
          $fields = array_merge($fields, $expr->getFields());
        }
      }
    }
    return array_unique($fields);
  }

  public function getRenderedDataType(?Api4Query $query): ?string {

    foreach ([$this->args[1] ?? NULL, $this->args[2] ?? NULL] as $arg) {
      if (!empty($arg['expr'][0])) {
        $expr = $arg['expr'][0];
        $type = $expr->getRenderedDataType($query);
        if ($type) {
          return $type;
        }
      }
    }
    return static::$dataType;
  }

}
