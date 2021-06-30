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

use Civi\API\Exception\UnauthorizedException;

/**
 * Sql column expression
 */
class SqlField extends SqlExpression {

  public $supportsExpansion = TRUE;

  protected function initialize() {
    if ($this->alias && $this->alias !== $this->expr) {
      throw new \API_Exception("Aliasing field names is not allowed, only expressions can have an alias.");
    }
    $this->fields[] = $this->expr;
  }

  public function render(array $fieldList): string {
    if (!isset($fieldList[$this->expr])) {
      throw new \API_Exception("Invalid field '{$this->expr}'");
    }
    if ($fieldList[$this->expr] === FALSE) {
      throw new UnauthorizedException("Unauthorized field '{$this->expr}'");
    }
    return $fieldList[$this->expr]['sql_name'];
  }

}
