<?php

/**
 * Description of a one-way link between two entities
 *
 * This is a basic SQL foreign key.
 */
class CRM_Core_Reference_Basic implements CRM_Core_Reference_Interface {
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

  public function matchesTargetTable($tableName) {
    return ($this->getTargetTable() === $tableName);
  }

  public function findReferences($targetDao) {
    $targetColumn = $this->getTargetKey();
    $params = array(
      1 => array($targetDao->$targetColumn, 'String')
    );
    $sql = <<<EOS
SELECT id
FROM {$this->getReferenceTable()}
WHERE {$this->getReferenceKey()} = %1
EOS;

    $daoName = CRM_Core_DAO_AllCoreTables::getClassForTable($this->getReferenceTable());
    $result = CRM_Core_DAO::executeQuery($sql, $params, TRUE, $daoName);
    return $result;
  }

  public function getReferenceCount($targetDao) {
    $targetColumn = $this->getTargetKey();
    $params = array(
      1 => array($targetDao->$targetColumn, 'String')
    );
    $sql = <<<EOS
SELECT count(id)
FROM {$this->getReferenceTable()}
WHERE {$this->getReferenceKey()} = %1
EOS;

    return array(
      'name' => implode(':', array('sql', $this->getReferenceTable(), $this->getReferenceKey())),
      'type' => get_class($this),
      'table' => $this->getReferenceTable(),
      'key' => $this->getReferenceKey(),
      'count' => CRM_Core_DAO::singleValueQuery($sql, $params)
    );
  }
}
