<?php

/**
 * Report as written by extensions that keep only the first rows by numbering them through a user variable.
 */
class CRM_Report_Form_LegacyTopRowsForm extends CRM_Report_Form_Contribute_Detail {

  const TOP_ROWS = 5;

  /**
   * @inheritDoc
   */
  public function buildRows($sql, &$rows) {
    CRM_Core_DAO::executeQuery('SET @legacy_rows := 0');
    $limit = (string) $this->_limit;
    $query = $limit === '' ? $sql : substr($sql, 0, strrpos($sql, $limit));
    parent::buildRows('SELECT * FROM (' . $query . ') top_rows WHERE (@legacy_rows := @legacy_rows + 1) <= ' . self::TOP_ROWS . " $limit", $rows);
  }

}
