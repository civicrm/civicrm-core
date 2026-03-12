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
 * SQL Function: JSON_EXTRACT
 */
class SqlFunctionJSON_EXTRACT extends SqlFunction {

  public $supportsExpansion = FALSE;

  protected static $category = self::CATEGORY_STRING;

  protected static function params(): array {
    return [
      [
        'max_expr' => 1,
        'must_be' => ['SqlField', 'SqlFunction', 'SqlEquation'],
        'optional' => FALSE,
      ],
      [
        'label' => ts('Path'),
        'max_expr' => 1,
        'must_be' => ['SqlString'],
        'optional' => FALSE,
      ],
    ];
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('JSON extract');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('Extracts values from a JSON field.');
  }

  public function getSqlFunctionName(): string {
    return 'JSON_EXTRACT';
  }

  public function render(Api4Query $query, bool $includeAlias = FALSE): string {
    $output = '';
    $field = $this->args[0]['expr'][0]->render($query);
    $path = $this->args[1]['expr'][0]->render($query);
    if (!preg_match('/[\'"]?\$/', $path)) {
      $path = "'$." . $path . "'";
    }

    $output .= "{$field}, {$path}";

    $renderedExpression = $this->renderExpression($output);
    $alias = ($includeAlias ? " AS `{$this->getAlias()}`" : '');
    return sprintf("JSON_UNQUOTE(%s)%s", $renderedExpression, $alias);
  }

  /**
   * Returns the alias to use for SELECT AS.
   *
   * @return string
   */
  public function getAlias(): string {
    return $this->alias ?? \CRM_Utils_String::munge(trim($this->expr, ' ()'), '_', 256);
  }

}
