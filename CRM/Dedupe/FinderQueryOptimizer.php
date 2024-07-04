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

  private array $contactIDs;

  private array $lookupParameters;

  /**
   * @throws \Civi\API\Exception\UnauthorizedException
   * @throws \CRM_Core_Exception
   */
  public function __construct(int $dedupeRuleGroupID, array $contactIDs, array $params) {
    $this->define('DedupeRuleGroup', 'RuleGroup', ['id' => $dedupeRuleGroupID]);
    $this->contactIDs = $contactIDs;
    $this->lookupParameters = $params;
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

  /**
   * Return the SQL query for the given rule - either for finding matching
   * pairs of contacts, or for matching against the $params variable (if set).
   *
   * @param array|null $params
   *   Params to dedupe against (queries against the whole contact set otherwise)
   * @param array $contactIDs
   *   Ids of the contacts to limit the SQL queries (whole-database queries otherwise)
   * @param array $rule
   * @param string $contactType
   *
   * @return string
   *   SQL query performing the search
   *   or NULL if params is present and doesn't have and for a field.
   *
   * @throws \CRM_Core_Exception
   * @internal do not call from outside tested core code. No universe uses Feb 2024.
   *
   */
  public function getQuery($params, $contactIDs, array $rule, string $contactType): ?string {

    $filter = $this->getRuleTableFilter($rule['rule_table'], $contactType);
    $contactIDFieldName = $this->getContactIDFieldName($rule['rule_table']);

    // build FROM (and WHERE, if it's a parametrised search)
    // based on whether the rule is about substrings or not
    if ($params) {
      $select = "t1.$contactIDFieldName id1, {$rule['rule_weight']} weight";
      $subSelect = 'id1, weight';
      $where = $filter ? ['t1.' . $filter] : [];
      $from = "{$rule['rule_table']} t1";
      $str = 'NULL';
      if (isset($params[$rule['rule_table']][$rule['rule_field']])) {
        $str = trim(CRM_Utils_Type::escape($params[$rule['rule_table']][$rule['rule_field']], 'String'));
      }
      if ($rule['rule_length']) {
        $where[] = "SUBSTR(t1.{$rule['rule_field']}, 1, {$rule['rule_length']}) = SUBSTR('$str', 1, {$rule['rule_length']})";
        $where[] = "t1.{$rule['rule_field']} IS NOT NULL";
      }
      else {
        $where[] = "t1.{$rule['rule_field']} = '$str'";
      }
    }
    else {
      $select = "t1.$contactIDFieldName id1, t2.$contactIDFieldName id2, {$rule['rule_weight']} weight";
      $subSelect = 'id1, id2, weight';
      $where = $filter ? [
        't1.' . $filter,
        't2.' . $filter,
      ] : [];
      $where[] = "t1.$contactIDFieldName < t2.$contactIDFieldName";
      $from = "{$rule['rule_table']} t1 INNER JOIN {$rule['rule_table']} t2 ON (" . self::getRuleFieldFilter($rule) . ")";
    }

    $query = "SELECT $select FROM $from WHERE " . implode(' AND ', $where);
    if ($contactIDs) {
      $cids = [];
      foreach ($contactIDs as $cid) {
        $cids[] = CRM_Utils_Type::escape($cid, 'Integer');
      }
      $query .= " AND t1.$contactIDFieldName IN (" . implode(',', $cids) . ")
        UNION $query AND  t2.$contactIDFieldName IN (" . implode(',', $cids) . ")";

      // The `weight` is ambiguous in the context of the union; put the whole
      // thing in a subquery.
      $query = "SELECT $subSelect FROM ($query) subunion";
    }
    return $query;
  }

  /**
   * Get any where filter that restricts the specific table.
   *
   * Generally this is along the lines of entity_table = civicrm_contact
   * although for the contact table it could be the id restriction.
   *
   * @param string $tableName
   * @param string $contactType
   *
   * @return string
   */
  private function getRuleTableFilter(string $tableName, string $contactType): string {
    if ($tableName === 'civicrm_contact') {
      return "contact_type = '{$contactType}'";
    }
    $dynamicReferences = CRM_Core_DAO::getDynamicReferencesToTable('civicrm_contact')[$tableName] ?? NULL;
    if (!$dynamicReferences) {
      return '';
    }
    if (!empty(CRM_Core_DAO::getDynamicReferencesToTable('civicrm_contact')[$tableName])) {
      return $dynamicReferences[1] . "= 'civicrm_contact'";
    }
    return '';
  }

  /**
   * @param array $rule
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  private function getRuleFieldFilter(array $rule): string {
    if ($rule['rule_length']) {
      $on = ["SUBSTR(t1.{$rule['rule_field']}, 1, {$rule['rule_length']}) = SUBSTR(t2.{$rule['rule_field']}, 1, {$rule['rule_length']})"];
      return "(" . implode(' AND ', $on) . ")";
    }
    $innerJoinClauses = [
      "t1.{$rule['rule_field']} IS NOT NULL",
      "t2.{$rule['rule_field']} IS NOT NULL",
      "t1.{$rule['rule_field']} = t2.{$rule['rule_field']}",
    ];

    if (in_array(CRM_Dedupe_BAO_DedupeRule::getFieldType($rule['rule_field'], $rule['rule_table']), CRM_Utils_Type::getTextTypes(), TRUE)) {
      $innerJoinClauses[] = "t1.{$rule['rule_field']} <> ''";
      $innerJoinClauses[] = "t2.{$rule['rule_field']} <> ''";
    }
    return "(" . implode(' AND ', $innerJoinClauses) . ")";
  }

  /**
   * Get the name of the field in the table that refers to the Contact ID.
   *
   * e.g in civicrm_contact this is 'id' whereas in civicrm_address this is
   * contact_id and in a custom field table it might be entity_id.
   *
   * @param string $tableName
   *
   * @return string
   *   Usually id, contact_id or entity_id.
   * @throws \CRM_Core_Exception
   */
  private function getContactIDFieldName(string $tableName): string {
    if ($tableName === 'civicrm_contact') {
      return 'id';
    }
    if (isset(CRM_Core_DAO::getDynamicReferencesToTable('civicrm_contact')[$tableName][0])) {
      return CRM_Core_DAO::getDynamicReferencesToTable('civicrm_contact')[$tableName][0];
    }
    if (isset(\CRM_Core_DAO::getReferencesToContactTable()[$tableName][0])) {
      return \CRM_Core_DAO::getReferencesToContactTable()[$tableName][0];
    }
    throw new CRM_Core_Exception('invalid field');
  }

}
