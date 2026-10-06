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
 * Class Api4QueryRelativeDateExpr
 *
 * Represents a raw SQL expression generated from a relative date parameter,
 * which should be included in the SQL compilation without quoting or escaping.
 *
 * @package Civi\Api4\Query
 */
class Api4QueryRelativeDateExpr {

  public function __construct(public string $expr) {}

}
