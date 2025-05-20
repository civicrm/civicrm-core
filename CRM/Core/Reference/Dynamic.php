<?php

/**
 * Description of a one-way link between two entities
 *
 * This is a generic, soft-foreign key based on a pair of columns (entity_id, entity_table).
 */
class CRM_Core_Reference_Dynamic extends CRM_Core_Reference_Basic {

  /**
   * @param string $tableName
   *
   * @return bool
   */
  public function matchesTargetTable($tableName) {
    $targetEntities = $this->getTargetEntities();
    if (!$targetEntities) {
      // Missing whitelist! That's not good, but we'll grandfather it in by accepting all entities.
      return TRUE;
    }
    return in_array(CRM_Core_DAO_AllCoreTables::getEntityNameForTable($tableName), $targetEntities, TRUE);
  }

  /**
   * Returns a list of all allowed values for $this->refTypeColumn
   *
   * @return array
   *   [ref_column_value => EntityName]
   *   Keys are the value stored in $this->refTypeColumn,
   *   Values are the name of the corresponding entity.
   */
  public function getTargetEntities(): array {
    $targetEntities = [];
    $bao = CRM_Core_DAO_AllCoreTables::getClassForTable($this->refTable);
    $targetTables = $bao::buildOptions($this->refTypeColumn, 'validate') ?: [];
    foreach ($targetTables as $value => $name) {
      // Old-style: Flat arrays of ['table_name' => 'Entity Label']
      // will be formatted like ['table_name' => 'table_name'] in 'validate' mode.
      // Note: Adding strtolower ensures both values are also lowercase && not something like 'Contact' => 'Contact'
      if (strtolower($value) === $name) {
        $targetEntities[$value] = CRM_Core_DAO_AllCoreTables::getEntityNameForTable($value);
      }
      // New-style: ['id' => 'value', 'name' => 'EntityName', 'label' => 'Entity Label'][]
      // will be formatted like ['value' => 'EntityName'] in 'validate' mode.
      else {
        $targetEntities[$value] = $name;
      }
    }
    return $targetEntities;
  }

  /**
   * Create a query to find references to a particular record.
   *
   * @param CRM_Core_DAO $targetDao
   *   The instance for which we want references.
   * @return CRM_Core_DAO
   *   a query-handle (like the result of CRM_Core_DAO::executeQuery)
   */
  public function findReferences($targetDao) {
    $sql = <<<EOS
SELECT id
FROM {$this->getReferenceTable()}
WHERE {$this->getReferenceKey()} = %1
AND {$this->getTypeColumn()} = %2
EOS;

    $params = $this->getQueryParams($targetDao);
    $daoName = CRM_Core_DAO_AllCoreTables::getClassForTable($this->getReferenceTable());
    $result = CRM_Core_DAO::executeQuery($sql, $params, TRUE, $daoName);
    return $result;
  }

  /**
   * @param CRM_Core_DAO $targetDao
   *
   * @return array
   */
  public function getReferenceCount($targetDao) {
    $sql = <<<EOS
SELECT count(id)
FROM {$this->getReferenceTable()}
WHERE {$this->getReferenceKey()} = %1
AND {$this->getTypeColumn()} = %2
EOS;

    return [
      'name' => implode(':', ['sql', $this->getReferenceTable(), $this->getReferenceKey()]),
      'type' => get_class($this),
      'table' => $this->getReferenceTable(),
      'key' => $this->getReferenceKey(),
      'count' => CRM_Core_DAO::singleValueQuery($sql, $this->getQueryParams($targetDao)),
    ];
  }

  /**
   * Gets query params needed by the find reference query
   * @param CRM_Core_DAO $targetDao
   * @return array[]
   */
  private function getQueryParams($targetDao): array {
    $targetColumn = $this->getTargetKey();

    // Look up option value for this entity. It's usually the table name, but not always.
    // If the lookup fails (some entities are missing the option list for the ref column),
    // then fall back on the table name.
    $targetEntity = CRM_Core_DAO_AllCoreTables::getEntityNameForClass(get_class($targetDao));
    $targetEntities = $this->getTargetEntities();
    $targetValue = array_search($targetEntity, $targetEntities) ?: $targetDao::getTableName();

    return [
      1 => [$targetDao->$targetColumn, 'String'],
      2 => [$targetValue, 'String'],
    ];
  }

}
