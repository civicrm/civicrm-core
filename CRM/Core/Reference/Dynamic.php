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
    return TRUE;
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
    $refColumn = $this->getReferenceKey();
    $targetColumn = $this->getTargetKey();

    $params = [
      1 => [$targetDao->$targetColumn, 'String'],
      // If anyone complains about $targetDao::getTableName(), then could use
      // "{get_class($targetDao)}::getTableName();"
      2 => [$targetDao::getTableName(), 'String'],
    ];

    $sql = <<<EOS
SELECT id
FROM {$this->getReferenceTable()}
WHERE {$refColumn} = %1
AND {$this->getTypeColumn()} = %2
EOS;

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
    $targetColumn = $this->getTargetKey();
    $params = [
      1 => [$targetDao->$targetColumn, 'String'],
      // If anyone complains about $targetDao::getTableName(), then could use
      // "{get_class($targetDao)}::getTableName();"
      2 => [$targetDao::getTableName(), 'String'],
    ];

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
      'count' => CRM_Core_DAO::singleValueQuery($sql, $params),
    ];
  }

}
