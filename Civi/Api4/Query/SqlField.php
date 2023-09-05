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
 * Sql column expression
 */
class SqlField extends SqlExpression {

  public $supportsExpansion = TRUE;

  protected function initialize() {
    if ($this->alias && $this->alias !== $this->expr && !strpos($this->expr, ':')) {
      throw new \CRM_Core_Exception("Aliasing field names is not allowed, only expressions can have an alias.");
    }
    $this->fields[] = $this->expr;
  }

  public function render(Api4Query $query, bool $includeAlias = FALSE): string {
    $field = $query->getField($this->expr, TRUE);
    $rendered = $field['sql_name'];
    if (!empty($field['sql_renderer'])) {
      $rendered = $field['sql_renderer']($field, $query);
    }
    return $rendered . ($includeAlias ? " AS `{$this->getAlias()}`" : '');
  }

  public static function getTitle(): string {
    return ts('Field');
  }

}
