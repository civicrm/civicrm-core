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

use Civi\Api4\Utils\CoreUtil;

/**
 * Sql function
 */
class SqlFunctionGROUP_CONCAT extends SqlFunction {

  public $supportsExpansion = TRUE;

  protected static $category = self::CATEGORY_AGGREGATE;

  protected static function params(): array {
    return [
      [
        'flag_before' => ['' => ts('All'), 'DISTINCT' => ts('Distinct Value'), 'UNIQUE' => ts('Unique Record')],
        'max_expr' => 1,
        'must_be' => ['SqlField', 'SqlFunction', 'SqlEquation'],
        'optional' => FALSE,
      ],
      [
        'name' => 'ORDER BY',
        'label' => ts('Order by'),
        'max_expr' => 1,
        'flag_after' => ['ASC' => ts('Ascending'), 'DESC' => ts('Descending')],
        'must_be' => ['SqlField'],
        'optional' => TRUE,
      ],
      [
        'name' => 'SEPARATOR',
        'max_expr' => 1,
        'must_be' => ['SqlString'],
        'optional' => TRUE,
        // @see self::formatOutput()
        'api_default' => [
          'expr' => ['"' . \CRM_Core_DAO::VALUE_SEPARATOR . '"'],
        ],
      ],
    ];
  }

  /**
   * Reformat result as array if using default separator
   *
   * @param string|null $dataType
   * @param array $values
   * @param string $key
   * @see \Civi\Api4\Utils\FormattingUtil::formatOutputValues
   */
  public function formatOutputValue(?string &$dataType, array &$values, string $key): void {
    $exprArgs = $this->getArgs();
    // By default, values are split into an array and formatted according to the field's dataType
    if ($this->getSerialize()) {
      $values[$key] = explode(\CRM_Core_DAO::VALUE_SEPARATOR, $values[$key]);
      // If the first expression is a SqlFunction/SqlEquation, allow it to control the dataType
      if (method_exists($exprArgs[0]['expr'][0], 'formatOutputValue')) {
        foreach (array_keys($values[$key]) as $index) {
          $exprArgs[0]['expr'][0]->formatOutputValue($dataType, $values[$key], $index);
        }
      }
      // Perform deduping by unique id
      if ($this->args[0]['prefix'] === ['UNIQUE'] && isset($values["_$key"])) {
        $ids = \CRM_Utils_Array::explodePadded($values["_$key"]);
        unset($values["_$key"]);
        foreach ($ids as $index => $id) {
          if (in_array($id, array_slice($ids, 0, $index))) {
            unset($values[$key][$index]);
          }
        }
        $values[$key] = array_values($values[$key]);
      }
    }
    // If using custom separator, preserve raw string
    else {
      $dataType = 'String';
    }
  }

  public function getSerialize(): ?int {
    $exprArgs = $this->getArgs();
    if (($exprArgs[2]['expr'][0]->expr ?? NULL) === \CRM_Core_DAO::VALUE_SEPARATOR) {
      return \CRM_Core_DAO::SERIALIZE_SEPARATOR_TRIMMED;
    }
    return NULL;
  }

  /**
   * @return string
   */
  public static function getTitle(): string {
    return ts('List');
  }

  /**
   * @return string
   */
  public static function getDescription(): string {
    return ts('All values in the grouping.');
  }

  public function render(Api4Query $query, bool $includeAlias = FALSE): string {
    $result = '';
    // Handle pseudo-prefix `UNIQUE` which is like `DISTINCT` but based on the record id rather than the field value
    if ($this->args[0]['prefix'] === ['UNIQUE']) {
      $this->args[0]['prefix'] = [];
      $expr = $this->args[0]['expr'][0];
      [$fieldPath] = explode(':', $expr->getFields()[0]);
      $field = $query->getField($expr->getFields()[0]);
      if ($field) {
        $idField = CoreUtil::getIdFieldName($field['entity']);
        $idFieldKey = substr($fieldPath, 0, 0 - strlen($field['name'])) . $idField;
        // Keep the ordering consistent
        if (empty($this->args[1]['prefix'])) {
          $this->args[1] = [
            'prefix' => ['ORDER BY'],
            'expr' => [SqlExpression::convert($idFieldKey)],
            'suffix' => [],
          ];
        }
        // Already a unique field, so DISTINCT will work fine
        if ($field['name'] === $idField) {
          $this->args[0]['prefix'] = ['DISTINCT'];
        }
        // Add a unique field on which to dedupe in postprocessing (@see self::formatOutputValue)
        elseif ($includeAlias) {
          $orderByKey = $this->args[1]['expr'][0]->getFields()[0];
          $extraSelectAlias = '_' . $this->getAlias();
          $extraSelect = SqlExpression::convert("GROUP_CONCAT($idFieldKey ORDER BY $orderByKey) AS $extraSelectAlias", TRUE);
          $query->selectAliases[$extraSelectAlias] = $extraSelect->getExpr();
          $result .= $extraSelect->render($query, TRUE) . ',';
        }
      }
    }
    $result .= parent::render($query, $includeAlias);
    return $result;
  }

}
