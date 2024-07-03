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
use Civi\API\EntityLookupTrait;
use Civi\Api4\DedupeRule;
use Civi\Api4\Generic\Result;

/**
 * Class to determine the combinations of queries to be used.
 *
 * @internal subject to change.
 */
class CRM_Dedupe_FinderQueryOptimizer {
  use EntityLookupTrait;

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
    $this->define('DedupeRuleGroup', 'RuleGroup', ['id' => $dedupeRuleGroupID]);
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

  public function getRules(): Result {
    return $this->rules;
  }

  /**
   * Is a file based reserved query configured.
   *
   * File based reserved queries were an early idea about how to optimise the dedupe queries.
   *
   * In theory extensions could implement them although there is no evidence any of them have.
   * However, if these are implemented by core or by extensions we should not attempt to optimise
   * the query by (e.g.) combining queries.
   *
   * In practice the queries implemented only return one query anyway
   *
   * @internal for core use only.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   *
   * @see \CRM_Dedupe_BAO_QueryBuilder_IndividualGeneral
   * @see \CRM_Dedupe_BAO_QueryBuilder_IndividualSupervised
   */
  public function isUseReservedQuery(): bool {
    return $this->lookup('RuleGroup', 'is_reserved') &&
      CRM_Utils_File::isIncludable('CRM/Dedupe/BAO/QueryBuilder/' . $this->lookup('RuleGroup', 'name') . '.php');
  }

}
