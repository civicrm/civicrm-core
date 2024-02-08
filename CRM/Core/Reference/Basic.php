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
   * @param string $refTable
   * @param string $refKey
   * @param string $targetTable
   * @param string $targetKey
   * @param string|null $refTypeColumn
   */
  public function __construct($refTable, $refKey, $targetTable = NULL, $targetKey = 'id', $refTypeColumn = NULL) {
    $this->refTable = $refTable;
    $this->refKey = $refKey;
    $this->targetTable = $targetTable;
    $this->targetKey = $targetKey;
    $this->refTypeColumn = $refTypeColumn;
  }

  /**
   * @return string
   */
  public function getReferenceTable() {
    return $this->refTable;
  }

  /**
   * @return string
   */
  public function getReferenceKey() {
    return $this->refKey;
  }

  /**
   * CRM_Core_Reference_Basic returns NULL.
   * CRM_Core_Reference_Dynamic returns the name of the dynamic column e.g. "entity_table".
   *
   * @return string|null
   */
  public function getTypeColumn() {
    return $this->refTypeColumn;
  }

  /**
   * @return string
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
   * @return array
   *   [table_name => EntityName]
   */
  public function getTargetEntities(): array {
    return [$this->targetTable => CRM_Core_DAO_AllCoreTables::getEntityNameForTable($this->targetTable)];
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
    $params = [
      1 => [$targetDao->$targetColumn, 'String'],
    ];
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
    $count = 0;
    if ($targetDao->{$targetColumn} !== '' && $targetDao->{$targetColumn} !== NULL) {

      $params = [
        1 => [$targetDao->{$targetColumn} ?? '', 'String'],
      ];
      $sql = <<<EOS
SELECT count(*)
FROM {$this->getReferenceTable()}
WHERE {$this->getReferenceKey()} = %1
EOS;
      $count = CRM_Core_DAO::singleValueQuery($sql, $params);
    }

    return [
      'name' => implode(':', ['sql', $this->getReferenceTable(), $this->getReferenceKey()]),
      'type' => get_class($this),
      'table' => $this->getReferenceTable(),
      'key' => $this->getReferenceKey(),
      'count' => $count,
    ];
  }

}
