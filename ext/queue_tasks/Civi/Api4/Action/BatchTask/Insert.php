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

namespace Civi\Api4\Action\BatchTask;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * @method $this setIncrement(string $increment)
 * @method string getIncrement()
 * @method $this setBatchLimit(int $limit)
 * @method string getBatchLimit()
 * @method $this setRuleGroupID(int $limit)
 * @method string getRuleGroupID()
 * @method $this setGroupID(int $limit)
 * @method string getGroupID()
 * @method $this setMinimumContactID(int $limit)
 * @method string getMinimumContactID()
 * @method string getStartTimestamp()
 * @method $this setStartDateTime(string $startDateTime)
 */
class Insert extends AbstractAction {
  protected string $increment = '60 minutes';
  protected string $startDateTime = '';
  protected int $batchLimit = 100;
  protected ?int $groupID = NULL;
  protected ?int $ruleGroupID = NULL;
  protected ?int $minimumContactID = NULL;

  public function _run(Result $result) {
    $values = [
      'start_timestamp' => $this->startDateTime ?: \CRM_Core_DAO::singleValueQuery('SELECT MIN(modified_date) FROM civicrm_contact WHERE modified_date > 0'),
      'increment' => $this->increment,
      'batch_limit' => $this->batchLimit,
      'group_id' => $this->groupID,
      'rule_group_id' => $this->ruleGroupID,
      'minimum_contact_id' => $this->minimumContactID,
    ];
    \Civi::queue('batch_merge')->createItem($values);
    return $result;
  }

}
