<?php
interface CRM_Core_Reference_Interface {
  public function getReferenceTable();

  public function getReferenceKey();

  /**
   * Determine if a given table is a target of this reference.
   *
   * @param string $tableName
   * @return bool
   */
  public function matchesTargetTable($tableName);

  /**
   * Create a query to find references to a particular record
   *
   * @param CRM_Core_DAO $targetDao the instance for which we want references
   * @return CRM_Core_DAO|NULL a query-handle (like the result of CRM_Core_DAO::executeQuery)
   */
  public function findReferences($targetDao);
}
