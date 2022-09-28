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
 * String sql expression
 */
class SqlString extends SqlExpression {

  protected function initialize() {
    // Remove surrounding quotes
    $str = substr($this->expr, 1, -1);
    // Unescape the outer quote character inside the string to prevent double-escaping in render()
    $quot = substr($this->expr, 0, 1);
    $backslash = chr(0) . 'backslash' . chr(0);
    $this->expr = str_replace(['\\\\', "\\$quot", $backslash], [$backslash, $quot, '\\\\'], $str);
  }

  public function render(array $fieldList): string {
    return '"' . \CRM_Core_DAO::escapeString($this->expr) . '"';
  }

}
