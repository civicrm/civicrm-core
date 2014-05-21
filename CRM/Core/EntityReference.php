<?php

/**
 * Description of a one-way link between two entities
 *
 * This could be a foreign key or a generic (entity_id, entity_table) pointer
 */
class CRM_Core_EntityReference {
  protected $refTable;
  protected $refKey;
  protected $refTypeColumn;
  protected $targetTable;
  protected $targetKey;

  function __construct($refTable, $refKey, $targetTable = NULL, $targetKey = 'id', $refTypeColumn = NULL) {
    $this->refTable = $refTable;
    $this->refKey = $refKey;
    $this->targetTable = $targetTable;
    $this->targetKey = $targetKey;
    $this->refTypeColumn = $refTypeColumn;
  }

  function getReferenceTable() {
    return $this->refTable;
  }

  function getReferenceKey() {
    return $this->refKey;
  }

  function getTypeColumn() {
    return $this->refTypeColumn;
  }

  function getTargetTable() {
    return $this->targetTable;
  }

  function getTargetKey() {
    return $this->targetKey;
  }

  /**
   * @return true if the reference can point to more than one type
   */
  function isGeneric() {
    return ($this->refTypeColumn !== NULL);
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
    $params = array(1 => array($targetDao->$targetColumn, 'String'));
    $sql = <<<EOS
SELECT id
FROM {$this->getReferenceTable()}
WHERE {$refColumn} = %1
EOS;
    if ($this->isGeneric()) {
      // If anyone complains about $dao::getTableName(), then could use
      // "$daoClass=get_class($dao); $daoClass::getTableName();"
      $params[2] = array($targetDao::getTableName(), 'String');
      $sql .= <<<EOS
    AND {$this->getTypeColumn()} = %2
EOS;
    }
    $daoName = CRM_Core_DAO_AllCoreTables::getClassForTable($this->getReferenceTable());
    $result = CRM_Core_DAO::executeQuery($sql, $params, TRUE, $daoName);
    return $result;
  }
}
