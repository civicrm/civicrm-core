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

  /**
   * @param $refTable
   * @param $refKey
   * @param null $targetTable
   * @param string $targetKey
   * @param null $refTypeColumn
   */
  function __construct($refTable, $refKey, $targetTable = NULL, $targetKey = 'id', $refTypeColumn = NULL) {
    $this->refTable = $refTable;
    $this->refKey = $refKey;
    $this->targetTable = $targetTable;
    $this->targetKey = $targetKey;
    $this->refTypeColumn = $refTypeColumn;
  }

  /**
   * @return mixed
   */
  function getReferenceTable() {
    return $this->refTable;
  }

  /**
   * @return mixed
   */
  function getReferenceKey() {
    return $this->refKey;
  }

  /**
   * @return null
   */
  function getTypeColumn() {
    return $this->refTypeColumn;
  }

  /**
   * @return null
   */
  function getTargetTable() {
    return $this->targetTable;
  }

  /**
   * @return string
   */
  function getTargetKey() {
    return $this->targetKey;
  }

  /**
   * @param string $tableName
   *
   * @return bool
   */
  public function matchesTargetTable($tableName) {
    return ($this->getTargetTable() === $tableName);
  }

  /**
   * @param CRM_Core_DAO $targetDao
   *
   * @return Object
   */
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

  /**
   * @param CRM_Core_DAO $targetDao
   *
   * @return array
   */
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
