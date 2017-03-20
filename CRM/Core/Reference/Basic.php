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
  public function __construct($refTable, $refKey, $targetTable = NULL, $targetKey = 'id', $refTypeColumn = NULL) {
    $this->refTable = $refTable;
    $this->refKey = $refKey;
    $this->targetTable = $targetTable;
    $this->targetKey = $targetKey;
    $this->refTypeColumn = $refTypeColumn;
  }

  /**
   * @return mixed
   */
  public function getReferenceTable() {
    return $this->refTable;
  }

  /**
   * @return mixed
   */
  public function getReferenceKey() {
    return $this->refKey;
  }

  /**
   * @return null
   */
  public function getTypeColumn() {
    return $this->refTypeColumn;
  }

  /**
   * @return null
   */
  public function getTargetTable() {
    return $this->targetTable;
  }

  /**
   * @return string
   */
  public function getTargetKey() {
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
    $select = 'id';
    // CRM-19385: Since id is removed, return all rows for cache tables.
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists($this->getReferenceTable(), 'id')) {
      $select = '*';
    }
    $params = array(
      1 => array($targetDao->$targetColumn, 'String'),
    );
    $sql = <<<EOS
SELECT {$select}
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
      1 => array($targetDao->$targetColumn, 'String'),
    );
    $sql = <<<EOS
SELECT count(*)
FROM {$this->getReferenceTable()}
WHERE {$this->getReferenceKey()} = %1
EOS;

    return array(
      'name' => implode(':', array('sql', $this->getReferenceTable(), $this->getReferenceKey())),
      'type' => get_class($this),
      'table' => $this->getReferenceTable(),
      'key' => $this->getReferenceKey(),
      'count' => CRM_Core_DAO::singleValueQuery($sql, $params),
    );
  }

}
