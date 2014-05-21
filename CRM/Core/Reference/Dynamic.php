<?php

/**
 * Description of a one-way link between two entities
 *
 * This is a generic, soft-foreign key based on a pair of columns (entity_id, entity_table).
 */
class CRM_Core_Reference_Dynamic extends CRM_Core_Reference_Basic {

  public function matchesTargetTable($tableName) {
    return TRUE;
  }

  /**
   * Create a query to find references to a particular record
   *
   * @param CRM_Core_DAO $targetDao the instance for which we want references
   * @return CRM_Core_DAO a query-handle (like the result of CRM_Core_DAO::executeQuery)
   */
  public function findReferences($targetDao) {
    $refColumn = $this->getReferenceKey();
    $targetColumn = $this->getTargetKey();

    $params = array(
      1 => array($targetDao->$targetColumn, 'String'),

      // If anyone complains about $targetDao::getTableName(), then could use
      // "{get_class($targetDao)}::getTableName();"
      2 => array($targetDao::getTableName(), 'String'),
    );

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
}
