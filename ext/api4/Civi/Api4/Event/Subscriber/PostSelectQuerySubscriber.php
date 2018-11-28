<?php

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Event\Events;
use Civi\Api4\Event\PostSelectQueryEvent;
use Civi\Api4\Query\Api4SelectQuery;
use Civi\Api4\Service\Schema\Joinable\Joinable;
use Civi\Api4\Utils\ArrayInsertionUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Changes the results of a select query, doing 1-n joins and unserializing data
 */
class PostSelectQuerySubscriber implements EventSubscriberInterface {

  /**
   * @inheritdoc
   */
  public static function getSubscribedEvents() {
    return [
      Events::POST_SELECT_QUERY => 'onPostQuery'
    ];
  }

  /**
   * @param PostSelectQueryEvent $event
   */
  public function onPostQuery(PostSelectQueryEvent $event) {
    $results = $event->getResults();
    $event->setResults($this->postRun($results, $event->getQuery()));
  }

  /**
   * @param array $results
   * @param Api4SelectQuery $query
   *
   * @return array
   */
  protected function postRun(array $results, Api4SelectQuery $query) {
    if (empty($results)) {
      return $results;
    }

    $fieldSpec = $query->getApiFieldSpec();
    $this->unserializeFields($results, $query->getEntity(), $fieldSpec);

    // Group the selects to avoid queries for each field
    $groupedSelects = $this->getJoinedDotSelects($query);
    foreach ($groupedSelects as $finalAlias => $selects) {
      $joinPath = $this->getJoinPathInfo($selects[0], $query);
      $selects = $this->formatSelects($finalAlias, $selects, $query);
      $joinResults = $this->getJoinResults($query, $finalAlias, $selects);
      $this->formatJoinResults($joinResults, $query, $finalAlias);

      // Insert join results into original result
      foreach ($results as &$primaryResult) {
        $baseId = $primaryResult['id'];
        $filtered = array_filter($joinResults, function ($res) use ($baseId) {
          return ($res['_base_id'] === $baseId);
        });
        $filtered = array_values($filtered);
        ArrayInsertionUtil::insert($primaryResult, $joinPath, $filtered);
      }
    }

    return array_values($results);
  }

  /**
   * @param array $joinResults
   * @param Api4SelectQuery $query
   * @param string $alias
   */
  private function formatJoinResults(&$joinResults, $query, $alias) {
    $join = $query->getJoinedTable($alias);
    $fields = [];
    foreach ($join->getEntityFields() as $field) {
      $name = explode('.', $field->getName());
      $fields[array_pop($name)] = $field->toArray();
    }
    if ($fields) {
      $this->unserializeFields($joinResults, NULL, $fields);
    }
  }

  /**
   * Unserialize values
   *
   * @param array $results
   * @param string $entity
   * @param array $fields
   */
  protected function unserializeFields(&$results, $entity, $fields = []) {
    if (empty($fields)) {
      $params = ['action' => 'get', 'includeCustom' => FALSE];
      $fields = civicrm_api4($entity, 'getFields', $params)->indexBy('name');
    }

    foreach ($results as &$result) {
      foreach ($result as $field => &$value) {
        if (!empty($fields[$field]['serialize']) && is_string($value)) {
          $serializationType = $fields[$field]['serialize'];
          $value = \CRM_Core_DAO::unSerializeField($value, $serializationType);
        }
      }
    }
  }

  /**
   * @param Api4SelectQuery $query
   *
   * @return array
   */
  private function getJoinedDotSelects(Api4SelectQuery $query) {
    // Remove selects that are not in a joined table
    $fkAliases = $query->getFkSelectAliases();
    $joinedDotSelects = array_filter(
      $query->getSelect(),
      function ($select) use ($fkAliases) {
        return isset($fkAliases[$select]);
      }
    );

    $selects = [];
    // group related selects by alias so they can be executed in one query
    foreach ($joinedDotSelects as $select) {
      $parts = explode('.', $select);
      $finalAlias = $parts[count($parts) - 2];
      $selects[$finalAlias][] = $select;
    }

    // sort by depth, e.g. email selects should be done before email.location
    uasort($selects, function ($a, $b) {
      $aFirst = $a[0];
      $bFirst = $b[0];
      return substr_count($aFirst, '.') > substr_count($bFirst, '.');
    });

    return $selects;
  }


  /**
   * @param array $selects
   * @param $serializationType
   * @param Api4SelectQuery $query
   *
   * @return array
   */
  private function getResultsForSerializedField(
    array $selects,
    $serializationType,
    Api4SelectQuery $query
  ) {
    // Get the alias (Selects are grouped and all target the same table)
    $sampleField = current($selects);
    $alias = strstr($sampleField, '.', TRUE);

    // Fetch the results with the serialized field
    $selects['serialized'] = $query::MAIN_TABLE_ALIAS . '.' . $alias;
    $serializedResults = $this->runWithNewSelects($selects, $query);
    $newResults = [];

    // Create a new results array, with a separate entry for each option value
    foreach ($serializedResults as $result) {
      $optionValues = \CRM_Core_DAO::unSerializeField(
        $result['serialized'],
        $serializationType
      );
      unset($result['serialized']);
      foreach ($optionValues as $value) {
        $newResults[] = array_merge($result, ['value' => $value]);
      }
    }

    $optionValueValues = array_unique(array_column($newResults, 'value'));
    $optionValues = $this->getOptionValuesFromValues(
      $selects,
      $query,
      $optionValueValues
    );
    $valueField = $alias . '.value';

    // Index by value
    foreach ($optionValues as $key => $subResult) {
      $optionValues[$subResult['value']] = $subResult;
      unset($subResult[$key]);

      // Exclude 'value' if not in original selects
      if (!in_array($valueField, $selects)) {
        unset($optionValues[$subResult['value']]['value']);
      }
    }

    // Replace serialized with the sub-select results
    foreach ($newResults as &$result) {
      $result = array_merge($result, $optionValues[$result['value']]);
      unset($result['value']);
    }

    return $newResults;
  }


  /**
   * Prepares selects for the subquery to fetch join results
   *
   * @param string $alias
   * @param array $selects
   * @param Api4SelectQuery $query
   *
   * @return array
   */
  private function formatSelects($alias, $selects, Api4SelectQuery $query) {
    $mainAlias = $query::MAIN_TABLE_ALIAS;
    $selectFields = [];

    foreach ($selects as $select) {
      $selectAlias = $query->getFkSelectAliases()[$select];
      $fieldAlias = substr($select, strrpos($select, '.') + 1);
      $selectFields[$fieldAlias] = $selectAlias;
    }

    $firstSelect = $selects[0];
    $pathParts = explode('.', $firstSelect);
    $numParts = count($pathParts);
    $parentAlias = $numParts > 2 ? $pathParts[$numParts - 3] : $mainAlias;

    $selectFields['id'] = sprintf('%s.id', $alias);
    $selectFields['_parent_id'] = $parentAlias . '.id';
    $selectFields['_base_id'] = $mainAlias . '.id';

    return $selectFields;
  }

  /**
   * @param array $selects
   * @param Api4SelectQuery $query
   *
   * @return array
   */
  private function runWithNewSelects(array $selects, Api4SelectQuery $query) {
    $aliasedSelects = array_map(function ($field, $alias) {
      return sprintf('%s as "%s"', $field, $alias);
    }, $selects, array_keys($selects));

    $newSelect = sprintf('SELECT DISTINCT %s', implode(", ", $aliasedSelects));
    $sql = str_replace("\n", ' ', $query->getQuery()->toSQL());
    $originalSelect = substr($sql, 0, strpos($sql, ' FROM'));
    $sql = str_replace($originalSelect, $newSelect, $sql);

    $relatedResults = [];
    $resultDAO = \CRM_Core_DAO::executeQuery($sql);
    while ($resultDAO->fetch()) {
      $relatedResult = [];
      foreach ($selects as $alias => $column) {
        $returnName = $alias;
        $alias = str_replace('.', '_', $alias);
        if (property_exists($resultDAO, $alias)) {
          $relatedResult[$returnName] = $resultDAO->$alias;
        }
      };
      $relatedResults[] = $relatedResult;
    }

    return $relatedResults;
  }

  /**
   * @param Api4SelectQuery $query
   * @param $alias
   * @param $selects
   * @return array
   */
  protected function getJoinResults(Api4SelectQuery $query, $alias, $selects) {
    $apiFieldSpec = $query->getApiFieldSpec();
    if (!empty($apiFieldSpec[$alias]['serialize'])) {
      $type = $apiFieldSpec[$alias]['serialize'];
      $joinResults = $this->getResultsForSerializedField($selects, $type, $query);
    }
    else {
      $joinResults = $this->runWithNewSelects($selects, $query);
    }

    // Remove results with no matching entries
    $joinResults = array_filter($joinResults, function ($result) {
      return !empty($result['id']);
    });

    return $joinResults;
  }

  /**
   * Separates a string like 'emails.location_type.label' into an array, where
   * each value in the array tells whether it is 1-1 or 1-n join type
   *
   * @param string $pathString
   *   Dot separated path to the field
   * @param Api4SelectQuery $query
   *
   * @return array
   *   Index is table alias and value is boolean whether is 1-to-many join
   */
  private function getJoinPathInfo($pathString, $query) {
    $pathParts = explode('.', $pathString);
    array_pop($pathParts); // remove field
    $path = [];
    $isMultipleChecker = function($alias) use ($query) {
      foreach ($query->getJoinedTables() as $table) {
        if ($table->getAlias() === $alias) {
          return $table->getJoinType() === Joinable::JOIN_TYPE_ONE_TO_MANY;
        }
      }
      return FALSE;
    };

    foreach ($pathParts as $part) {
      $path[$part] = $isMultipleChecker($part);
    }

    return $path;
  }

  /**
   * Get all the option_value values required in the query
   *
   * @param array $selects
   * @param Api4SelectQuery $query
   * @param array $values
   *
   * @return array
   */
  private function getOptionValuesFromValues(
    array $selects,
    Api4SelectQuery $query,
    array $values
  ) {
    $sampleField = current($selects);
    $alias = strstr($sampleField, '.', TRUE);

    // Get the option value table that was joined
    $relatedTable = NULL;
    foreach ($query->getJoinedTables() as $joinedTable) {
      if ($joinedTable->getAlias() === $alias) {
        $relatedTable = $joinedTable;
      }
    }

    // We only want subselects related to the joined table
    $subSelects = array_filter($selects, function ($select) use ($alias) {
      return strpos($select, $alias) === 0;
    });

    // Fetch all related option_value entries
    $valueField = $alias . '.value';
    $subSelects[] = $valueField;
    $tableName = $relatedTable->getTargetTable();
    $conditions = $relatedTable->getExtraJoinConditions();
    $conditions[] = $valueField . ' IN ("' . implode('", "', $values) . '")';
    $subQuery = new \CRM_Utils_SQL_Select($tableName . ' ' . $alias);
    $subQuery->where($conditions);
    $subQuery->select($subSelects);
    $subResults = $subQuery->execute()->fetchAll();

    return $subResults;
  }

}
