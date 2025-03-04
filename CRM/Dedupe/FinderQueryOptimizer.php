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

/**
 * Class to determine the combinations of queries to be used.
 *
 * @internal subject to change.
 */
class CRM_Dedupe_FinderQueryOptimizer {
  use EntityLookupTrait;

  private array $queries = [];

  /**
   * Threshold weight for merge.
   *
   * This starts with an unreachable number and is set to the correct number
   * provided there is a valid rule.
   *
   * @var int
   */
  private int $threshold = 999999;

  private array $contactIDs = [];

  private array $lookupParameters;

  /**
   * @throws \Civi\API\Exception\UnauthorizedException
   * @throws \CRM_Core_Exception
   */
  public function __construct(int $dedupeRuleGroupID, array $contactIDs, array $params) {
    $this->define('DedupeRuleGroup', 'RuleGroup', ['id' => $dedupeRuleGroupID]);
    foreach ($contactIDs as $cid) {
      $this->contactIDs[] = CRM_Utils_Type::escape($cid, 'Integer');
    }
    $this->lookupParameters = $params;
    $rules = DedupeRule::get(FALSE)
      ->addSelect('*', 'dedupe_rule_group_id.threshold')
      ->addWhere('dedupe_rule_group_id', '=', $dedupeRuleGroupID)
      ->addOrderBy('rule_weight', 'DESC')
      ->execute();
    foreach ($rules as $index => $rule) {
      // Filter out the rule if this is a parameters lookup & it is not in the rules.
      if (!$this->lookupParameters || (array_key_exists($rule['rule_table'], $this->lookupParameters) && array_key_exists($rule['rule_field'], $this->lookupParameters[$rule['rule_table']]))) {
        $key = $rule['rule_table'] . '.' . $rule['rule_field'] . '.' . $rule['rule_weight'];
        $this->queries[$key] = [
          'table' => $rule['rule_table'],
          'field' => $rule['rule_field'],
          'weight' => $rule['rule_weight'],
          'length' => $rule['rule_length'],
          'key' => $key,
          'order' => $index + 1,
        ];
        $this->queries[$key]['query'] = $this->getQuery($this->queries[$key]);
      }
      $this->threshold = $rule['dedupe_rule_group_id.threshold'];
    }
  }

  /**
   * Return the SQL query for the given rule - either for finding matching
   * pairs of contacts, or for matching against the $params variable (if set).
   *
   * @param array $rule
   *
   * @return string
   *   SQL query performing the search
   *   or NULL if params is present and doesn't have and for a field.
   *
   * @throws \CRM_Core_Exception
   * @internal do not call from outside tested core code. No universe uses Feb 2024.
   *
   */
  public function getQuery(array $rule): ?string {

    $filter = $this->getRuleTableFilter($rule['table']);
    $contactIDFieldName = $this->getContactIDFieldName($rule['table']);

    // build FROM (and WHERE, if it's a parametrised search)
    // based on whether the rule is about substrings or not
    if ($this->lookupParameters) {
      $select = "t1.$contactIDFieldName id1, {$rule['weight']} weight";
      $subSelect = 'id1, weight';
      $where = $filter ? ['t1.' . $filter] : [];
      $from = "{$rule['table']} t1";
      $str = 'NULL';
      if (isset($this->lookupParameters[$rule['table']][$rule['field']])) {
        $str = trim(CRM_Utils_Type::escape($this->lookupParameters[$rule['table']][$rule['field']], 'String'));
      }
      if ($rule['length']) {
        $where[] = "SUBSTR(t1.{$rule['field']}, 1, {$rule['length']}) = SUBSTR('$str', 1, {$rule['length']})";
        $where[] = "t1.{$rule['field']} IS NOT NULL";
      }
      else {
        $where[] = "t1.{$rule['field']} = '$str'";
      }
    }
    else {
      $select = "t1.$contactIDFieldName id1, t2.$contactIDFieldName id2, {$rule['weight']} weight";
      $subSelect = 'id1, id2, weight';
      $where = $filter ? [
        't1.' . $filter,
        't2.' . $filter,
      ] : [];
      $where[] = "t1.$contactIDFieldName < t2.$contactIDFieldName";
      $from = "{$rule['table']} t1 INNER JOIN {$rule['table']} t2 ON (" . self::getRuleFieldFilter($rule) . ")";
    }

    $sql = "SELECT $select FROM $from WHERE " . implode(' AND ', $where);
    if ($this->contactIDs) {
      $cids = $this->contactIDs;
      $sql .= " AND t1.$contactIDFieldName IN (" . implode(',', $cids) . ")
        UNION $sql AND  t2.$contactIDFieldName IN (" . implode(',', $cids) . ")";

      // The `weight` is ambiguous in the context of the union; put the whole
      // thing in a subquery.
      $sql = "SELECT $subSelect FROM ($sql) subunion";
    }
    return $sql;
  }

  /**
   * Get any where filter that restricts the specific table.
   *
   * Generally this is along the lines of entity_table = civicrm_contact
   * although for the contact table it could be the id restriction.
   *
   * @param string $tableName
   *
   * @return string
   */
  private function getRuleTableFilter(string $tableName): string {
    if ($tableName === 'civicrm_contact') {
      return "contact_type = '" . $this->lookup('RuleGroup', 'contact_type') . "'";
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
    if ($rule['length']) {
      $on = ["SUBSTR(t1.{$rule['field']}, 1, {$rule['length']}) = SUBSTR(t2.{$rule['field']}, 1, {$rule['length']})"];
      return "(" . implode(' AND ', $on) . ")";
    }
    $innerJoinClauses = [
      "t1.{$rule['field']} IS NOT NULL",
      "t2.{$rule['field']} IS NOT NULL",
      "t1.{$rule['field']} = t2.{$rule['field']}",
    ];

    if (in_array(CRM_Dedupe_BAO_DedupeRule::getFieldType($rule['field'], $rule['table']), CRM_Utils_Type::getTextTypes(), TRUE)) {
      $innerJoinClauses[] = "t1.{$rule['field']} <> ''";
      $innerJoinClauses[] = "t2.{$rule['field']} <> ''";
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

  /**
   * Get the queries to fill the table for the various rules.
   *
   * Return a set of SQL queries whose cummulative weights will mark matched
   * records for the RuleGroup::thresholdQuery() to retrieve.
   *
   * @internal do not call from outside tested core code.
   *
   * @return array
   */
  public function getRuleQueries(): array {
    $queries = [];
    foreach ($this->queries as $rule) {
      $queries[$rule['key']] = $rule['query'];
    }
    return $queries;
  }

  /**
   * Get any fields that should be combined.
   *
   * For example if we always use first_name and last_name together then
   * we combine these 2 queries.
   *
   * @return array
   */
  public function getCombinableQueries(): array {
    $possibleCombinations = [];
    $validQueryCombinations = $this->getValidCombinations();

    // First we compile an array of all possible field combinations
    // ie each set of fields that occurs together at least once.
    foreach ($validQueryCombinations as $validQueryCombination) {
      $fieldCombinations = $this->getPowerSet($validQueryCombination);
      foreach ($fieldCombinations as $fieldCombination) {
        if (count($fieldCombination) > 1) {
          $possibleCombinations[implode(',', array_values($fieldCombination))] = $fieldCombination;
        }
      }
    }
    $combinedFields = [];
    foreach ($possibleCombinations as $key => $possibleCombination) {
      if (!$this->isCombinationAlwaysTogether(array_values($this->getValidCombinations()), $possibleCombination)) {
        unset($possibleCombinations[$key]);
      }
    }
    // Now prune any subsets.
    foreach ($possibleCombinations as $key => $possibleCombination) {
      $otherCombinations = $possibleCombinations;
      unset($otherCombinations[$key]);
      if (!$this->isCombinationAlreadyCovered($otherCombinations, $possibleCombination)) {
        $combinedFields[] = $possibleCombination;
      }
    }

    return $combinedFields;
  }

  /**
   * Get queries with queries within the same table that MUST be combined combined.
   *
   * For example if both first_name and last_name are required to meet the threshold then
   * use one query that includes both.
   */
  public function getOptimizedQueries(): array {
    $queries = $this->queries;
    // We want to combine cross-tables but looking to do that as a follow on since that will require some
    // more work to figure out. For single table regex does get us there.
    foreach ($this->getCombinableQueriesByTable() as $queryCombinations) {
      foreach ($queryCombinations as $queryDetails) {
        if (count($queryDetails) < 2) {
          // We can't yet combine across tables so skip this one for now.
          continue;
        }
        $comboQueries = ['field' => [], 'criteria' => [], 'weight' => 0, 'table' => '', 'query' => ''];
        foreach ($queryDetails as $queryDetail) {
          $comboQueries['table'] = $queryDetail['table'];
          $comboQueries['weight'] += $queryDetail['weight'];
          $comboQueries['field'][] = $queryDetail['field'];
          $comboQueries['query'] = $queryDetail['query'];
          $criteria = [];
          // The part of the query that relates to the field is in double brackets like this.
          // ((t1.first_name IS NOT NULL AND t2.first_name IS NOT NULL AND t1.first_name = t2.first_name AND t1.first_name <> '' AND t2.first_name <> ''))
          preg_match('/\((\(.+?\))\)/m', $queryDetail['query'], $criteria);
          $comboQueries['criteria'][] = $criteria[1];
          unset($queries[$queryDetail['key']]);
        }
        $combinedKey = $comboQueries['table'] . '.' . implode('_', $comboQueries['field']) . '.' . $comboQueries['weight'];
        $combinedQuery['query'] = preg_replace('/\((\(.+?\))\)/m', implode(' AND ', $comboQueries['criteria']), $comboQueries['query']);
        $combinedQuery['query'] = preg_replace('/( \d+ weight )/m', ' ' . $comboQueries['weight'] . ' weight ', $combinedQuery['query']);
        $combinedQuery['weight'] = $comboQueries['weight'];
        $combinedQuery['key'] = $combinedKey;
        $queries[$combinedKey] = $combinedQuery;
      }
    }
    uasort($queries, [$this, 'sortWeightDescending']);
    $tableQueryFormat = [];
    foreach ($queries as $query) {
      $tableQueryFormat[$query['key']] = $query['query'];
    }
    return $tableQueryFormat;
  }

  /**
   * Get queries that are combinable, keyed by table.
   *
   * A combinable query is one where the fields must both / all be matches if any of them
   * are. e.g. if first_name is only ever enough to meet the threshold if last_name
   * is also a match.
   *
   * @return array
   *   Combinable query. Within each combination queries are keyed
   *   by table. eg. if the threshold can only be met if first_name, last_name & email
   *   are all matches then it would return the following.
   *
   *   [
   *     ['civicrm_email' => [['field_name' => 'email'...., ], 'civicrm_contact' => [['field_name' => 'first_name'....], ['field_name' => 'last_name'....]]],
   *   ]
   *
   */
  public function getCombinableQueriesByTable(): array {
    $singleTableCombinableQueries = [];
    foreach ($this->getCombinableQueries() as $index => $combo) {
      foreach ($combo as $key) {
        $query = $this->queries[$key];
        $singleTableCombinableQueries[$index][$query['table']][$query['field']] = $query;
      }
    }
    return $singleTableCombinableQueries;
  }

  private function sortWeightDescending($a, $b) {
    return ($a['weight'] > $b['weight']) ? -1 : 1;
  }

  /**
   * Is the field combination already covered by another one in the array.
   *
   * Each field combination represents 2 ore more fields that are always used
   * together to fulfill the rule & hence can be combined. For example we have a
   * rule where 12 points are required and first name & last name are both worth 5
   * points and birth date and nick name are both worth 2 points then the threshold
   * can only be met with BOTH first name & last name & hence we should combine their
   * queries.
   *
   * This would be true if the combination is for 2 fields but the same combination
   * is already present in the opposite order or within a array covering 3 fields.
   * fields. We want to combine the greatest number of fields that are combinable
   * (as that will minimise queries) so the 3 field array is better than the 2 field array.
   *
   * @param array $allCombinations
   * @param array $combination
   *
   * @return bool
   */
  private function isCombinationAlreadyCovered(array $allCombinations, array $combination): bool {
    foreach ($allCombinations as $otherCombination) {
      $isAllFieldsFound = empty(array_diff($combination, $otherCombination));
      $isSomeFieldsFound = !empty(array_intersect($combination, $otherCombination));
      if ($isSomeFieldsFound && $isAllFieldsFound) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Is the given combination of fields always used together?
   *
   * For example if the only way to get to the threshold is to have
   * both first_name & last_name and these 2 fields are being passed in here
   * then this will return TRUE.
   *
   * @param array $allCombinations
   * @param array $combination
   *   e.g ['civicrm_contact.first_name.7', 'civicrm_contact.last_name.8']
   *
   * @return bool
   */
  private function isCombinationAlwaysTogether(array $allCombinations, array $combination): bool {
    foreach ($allCombinations as $validCombination) {
      $someCombinationFieldsUsed = !empty(array_intersect($combination, array_keys($validCombination)));
      $allCombinationFieldsUsed = empty(array_diff($combination, array_keys($validCombination)));
      if ($someCombinationFieldsUsed && !$allCombinationFieldsUsed) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Get all combinations of the queries.
   *
   * This is taken from https://www.oreilly.com/library/view/php-cookbook/1565926811/ch04s25.html
   * which assures us it is called a power set...
   *
   * @return array[]
   */
  private function getPowerSet($queries): array {
    // initialize by adding the empty set. This is necessary for the logic of this function.
    $results = [[]];
    foreach (array_reverse(array_keys($queries)) as $element) {
      foreach ($results as $combination) {
        $results[] = array_merge([$element], $combination);
      }
    }
    return $results;
  }

  /**
   * @return array[]
   */
  public function getValidCombinations(): array {
    $combinations = [];
    foreach ($this->getPowerSet($this->queries) as $set) {
      $combination = [];
      foreach ($set as $queryKey) {
        $combination[$queryKey] = $this->queries[$queryKey]['weight'];
      }
      if (array_sum($combination) >= $this->threshold) {
        $combinations[] = $combination;
      }
    }
    foreach ($combinations as $key => $combination) {
      // Check if the combination was already enough without the last item.
      // If so we discard it as that combination is already in the array (or has
      // already been discorded on the same basis).
      array_pop($combination);
      if (array_sum($combination) >= $this->threshold) {
        unset($combinations[$key]);
      }
    }
    return $combinations;
  }

  /**
   * It the optimizer being used to lookup existing contacts based on input parameters.
   *
   * @internal
   *
   * @return bool
   */
  public function isLookupMode(): bool {
    return !empty($this->lookupParameters);
  }

}
