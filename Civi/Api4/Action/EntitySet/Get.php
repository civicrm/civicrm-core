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

namespace Civi\Api4\Action\EntitySet;

use Civi\Api4\Generic\DAOGetAction;
use Civi\Api4\Generic\Result;
use Civi\Api4\Generic\Traits\GroupAndHavingParamTrait;
use Civi\Api4\Generic\Traits\SelectParamTrait;
use Civi\Api4\Query\Api4EntitySetQuery;

/**
 * @method array getSets()
 * @method setSets(array $sets)
 */
class Get extends \Civi\Api4\Generic\AbstractQueryAction {

  use SelectParamTrait;
  use GroupAndHavingParamTrait;

  /**
   * Api queries to combine using UNION DISTINCT or UNION ALL
   *
   * The SQL rules of unions apply: each query must SELECT the same number of fields
   * with matching types (in order). Field names do not have to match; (returned fields
   * will use the name from the first query).
   *
   * @var array
   */
  protected $sets = [];

  /**
   * @param string $type
   *   'UNION DISTINCT' or 'UNION ALL'
   * @param \Civi\Api4\Generic\DAOGetAction $apiRequest
   * @return $this
   */
  public function addSet(string $type, DAOGetAction $apiRequest) {
    $this->sets[] = [$type, $apiRequest->getEntityName(), $apiRequest->getActionName(), $apiRequest->getParams()];
    return $this;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function _run(Result $result) {
    $query = new Api4EntitySetQuery($this);
    $rows = $query->run();
    \CRM_Utils_API_HTMLInputCoder::singleton()->decodeRows($rows);
    $result->exchangeArray($rows);
  }

}
