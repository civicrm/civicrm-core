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
use Civi\Api4\DedupeRule;

/**
 * Class to determine the combinations of queries to be used.
 *
 * @internal subject to change.
 */
class CRM_Dedupe_FinderQueryOptimizer {

  private array $queries;

  /**
   * @var mixed
   */
  private int $threshold;

  private int $dedupeRuleGroupID;

  private Civi\Api4\Generic\Result $rules;

  /**
   * @throws \Civi\API\Exception\UnauthorizedException
   * @throws \CRM_Core_Exception
   */
  public function __construct(int $dedupeRuleGroupID) {
    $this->dedupeRuleGroupID = $dedupeRuleGroupID;
    $this->rules = DedupeRule::get(FALSE)
      ->addSelect('*', 'dedupe_rule_group_id.threshold')
      ->addWhere('dedupe_rule_group_id', '=', $dedupeRuleGroupID)
      ->addOrderBy('rule_weight', 'DESC')
      ->execute();
    foreach ($this->rules as $index => $rule) {
      $key = $rule['rule_table'] . '.' . $rule['rule_field'] . '.' . $rule['rule_weight'];
      $this->queries[$key] = [
        'table' => $rule['rule_table'],
        'field' => $rule['rule_field'],
        'weight' => $rule['rule_weight'],
        'key' => $key,
        'order' => $index + 1,
      ];
      $this->threshold = $rule['dedupe_rule_group_id.threshold'];
    }
  }

  public function getRules(): \Civi\Api4\Generic\Result {
    return $this->rules;
  }

}
