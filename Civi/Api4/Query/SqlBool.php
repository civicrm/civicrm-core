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
 * Boolean sql expression
 */
class SqlBool extends SqlExpression {

  protected static $dataType = 'Boolean';

  protected function initialize() {
  }

  public function render(Api4Query $query, bool $includeAlias = FALSE): string {
    return ($this->expr === 'TRUE' ? '1' : '0') . ($includeAlias ? " AS `{$this->getAlias()}`" : '');
  }

  public static function getTitle(): string {
    return ts('Boolean');
  }

}
