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

namespace Civi\Api4\Generic\Traits;

use Civi\Api4\Utils\CoreUtil;

/**
 * @method $this setHaving(array $clauses)
 * @method array getHaving()
 * @method $this setGroupBy(array $clauses)
 * @method array getGroupBy()
 * @package Civi\Api4\Generic
 */
trait GroupAndHavingParamTrait {

  /**
   * Field(s) by which to group the results.
   *
   * @var array
   */
  protected $groupBy = [];

  /**
   * Clause for filtering results after grouping and filters are applied.
   *
   * Each expression should correspond to an item from the SELECT array.
   *
   * @var array
   */
  protected $having = [];

  /**
   * @param string $field
   * @return $this
   */
  public function addGroupBy(string $field) {
    $this->groupBy[] = $field;
    return $this;
  }

  /**
   * @param string $expr
   * @param string $op
   * @param mixed $value
   * @param bool $isExpression
   * @return $this
   * @throws \CRM_Core_Exception
   */
  public function addHaving(string $expr, string $op, $value = NULL, bool $isExpression = FALSE) {
    if (!in_array($op, CoreUtil::getOperators())) {
      throw new \CRM_Core_Exception('Unsupported operator');
    }
    $this->having[] = [$expr, $op, $value, $isExpression];
    return $this;
  }

}
