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
 * Numeric sql expression
 */
class SqlNumber extends SqlExpression {

  protected function initialize() {
    \CRM_Utils_Type::validate($this->expr, 'Float');
  }

  public function render(array $fieldList): string {
    return $this->expr;
  }

}
