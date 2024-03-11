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

  /**
   * @param array $tableQueries
   *   Queries in format from DedupeRuleGroup - may change.
   *   Currently looks like
   *   [
   *     'civicrm_email.email.16' => 'SELECT ...',
   *     'civicrm_contact.first_name.7' => 'SELECT ...',
   *     'civicrm_phone.phone_numeric.5' => 'SELECT ...',
   *   ];
   *
   *   Where the key consists of the table, field and weight.
   *
   * @param int $threshold
   *
   * @internal
   *
   */
  public function __construct(array $tableQueries, int $threshold) {
    $order = 1;
    $this->queries = [];
    foreach ($tableQueries as $key => $query) {
      $parts = explode('.', $key);
      $this->queries[$key] = [
        'table' => $parts[0],
        'field' => $parts[1],
        'weight' => (int) $parts[2],
        'query' => $query,
        'key' => $key,
        'order' => $order,
      ];
      $order++;
    }
    $this->threshold = $threshold;
  }

  public function getQueryCombinations(): array {
    $queries = [];
    foreach ($this->getValidCombinations() as $combination) {
      foreach ($combination as $key => $query) {
        $queries[$key] = $this->queries[$key]['query'];
      }
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

  public function getOptimizedQueries(): array {
    $queries = $this->queries;
    foreach ($this->getCombinableQueries() as $combo) {
      $comboQueries = ['field' => [], 'criteria' => [], 'weight' => 0, 'table' => '', 'query' => ''];
      foreach ($combo as $inComboQuery) {
        $queryDetail = $this->queries[$inComboQuery];
        $comboQueries['table'] = $queryDetail['table'];
        $comboQueries['weight'] += $queryDetail['weight'];
        $comboQueries['field'][] = $queryDetail['field'];
        $comboQueries['query'] = $queryDetail['query'];
        $criteria = [];
        // The part of the query that relates to the field is in double brackets like this.
        // ((t1.first_name IS NOT NULL AND t2.first_name IS NOT NULL AND t1.first_name = t2.first_name AND t1.first_name <> '' AND t2.first_name <> ''))
        preg_match('/\((\(.+?\))\)/m', $queryDetail['query'], $criteria);
        $comboQueries['criteria'][] = $criteria[1];
        unset($queries[$inComboQuery]);
      }
      $combinedKey = $comboQueries['table'] . '.' . implode('_', $comboQueries['field']) . '.' . $comboQueries['weight'];
      $combinedQuery['query'] = preg_replace('/\((\(.+?\))\)/m', implode(' AND ', $comboQueries['criteria']), $comboQueries['query']);
      $combinedQuery['query'] = preg_replace('/( \d+ weight )/m', ' ' . $comboQueries['weight'] . ' weight ', $combinedQuery['query']);
      $combinedQuery['weight'] = $comboQueries['weight'];
      $combinedQuery['key'] = $combinedKey;
      $queries[$combinedKey] = $combinedQuery;
    }
    uasort($queries, [$this, 'sortWeightDescending']);
    $tableQueryFormat = [];
    foreach ($queries as $query) {
      $tableQueryFormat[$query['key']] = $query['query'];
    }
    return $tableQueryFormat;
  }

  private function sortWeightDescending($a, $b) {
    return ($a['weight'] < $b['weight']) ? -1 : 1;
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
  private function isCombinationAlreadyCovered(array $allCombinations,  array $combination): bool {
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
   * @param array $combination
   *   e.g ['civicrm_contact.first_name.7', 'civicrm_contact.last_name.8']
   *
   * @return bool
   */
  private function isCombinationAlwaysTogether(array $allCombinations,  array $combination): bool {
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

}
