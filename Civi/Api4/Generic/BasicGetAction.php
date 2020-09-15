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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace Civi\Api4\Generic;

use Civi\API\Exception\NotImplementedException;
use Civi\Api4\Utils\FormattingUtil;

/**
 * Retrieve $ENTITIES based on criteria specified in the `where` parameter.
 *
 * Use the `select` param to determine which fields are returned, defaults to `[*]`.
 */
class BasicGetAction extends AbstractGetAction {
  use Traits\ArrayQueryActionTrait;

  /**
   * @var callable
   *   Function(BasicGetAction $thisAction): array[]
   */
  private $getter;

  /**
   * Basic Get constructor.
   *
   * @param string $entityName
   * @param string $actionName
   * @param callable $getter
   */
  public function __construct($entityName, $actionName, $getter = NULL) {
    parent::__construct($entityName, $actionName);
    $this->getter = $getter;
  }

  /**
   * Fetch results from the getter then apply filter/sort/select/limit.
   *
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $this->setDefaultWhereClause();
    $this->expandSelectClauseWildcards();
    $values = $this->getRecords();
    $this->formatRawValues($values);
    $this->queryArray($values, $result);
  }

  /**
   * BasicGet is a general-purpose get action for non-DAO-based entities.
   *
   * Useful for fetching records from files or other places.
   * Specify any php function to retrieve the records, and this class will
   * automatically filter, sort, select & limit the raw data from the callback.
   *
   * This action is implemented in one of two ways:
   * 1. Invoke this class directly by passing a callable ($getter) to the constructor. BasicEntity does this by default.
   *    The function is passed a copy of $this action as it's first argument.
   * 2. Extend this class and override this function.
   *
   * Either way, this function should return an array of arrays, each representing one retrieved object.
   *
   * The simplest thing for your getter function to do is return every full record
   * and allow this class to automatically do the sorting and filtering.
   *
   * Sometimes however that may not be practical for performance reasons.
   * To optimize your getter, it can use the following helpers from $this:
   *
   * Use this->_itemsToGet() to match records to field values in the WHERE clause.
   * Note the WHERE clause can potentially be very complex and it is not recommended
   * to parse $this->where yourself.
   *
   * Use $this->_isFieldSelected() to check if a field value is called for - useful
   * if loading the field involves expensive calculations.
   *
   * Be careful not to make assumptions, e.g. if LIMIT 100 is specified and your getter "helpfully" truncates the list
   * at 100 without accounting for WHERE, ORDER BY and LIMIT clauses, the final filtered result may be very incorrect.
   *
   * @return array
   * @throws \Civi\API\Exception\NotImplementedException
   */
  protected function getRecords() {
    if (is_callable($this->getter)) {
      $this->addCallbackToDebugOutput($this->getter);
      return call_user_func($this->getter, $this);
    }
    throw new NotImplementedException('Getter function not found for api4 ' . $this->getEntityName() . '::' . $this->getActionName());
  }

  /**
   * Evaluate :pseudoconstant suffix expressions and replace raw values with option values
   *
   * @param $records
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  protected function formatRawValues(&$records) {
    // Pad $records and $fields with pseudofields
    $fields = $this->entityFields();
    foreach ($records as &$values) {
      foreach ($this->entityFields() as $field) {
        if (!empty($field['options'])) {
          foreach (FormattingUtil::$pseudoConstantSuffixes as $suffix) {
            $pseudofield = $field['name'] . ':' . $suffix;
            if (!isset($values[$pseudofield]) && isset($values[$field['name']]) && $this->_isFieldSelected($pseudofield)) {
              $values[$pseudofield] = $values[$field['name']];
              $fields[$pseudofield] = $field;
            }
          }
        }
      }
    }
    // Swap raw values with pseudoconstants
    FormattingUtil::formatOutputValues($records, $fields, $this->getEntityName(), $this->getActionName());
  }

}
