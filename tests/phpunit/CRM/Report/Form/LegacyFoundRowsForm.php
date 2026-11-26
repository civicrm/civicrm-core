<?php

/**
 * Report as written by extensions that still request SQL_CALC_FOUND_ROWS.
 *
 * The displayed query also repeats a column name, which a top-level SELECT allows but a derived table does not.
 */
class CRM_Report_Form_LegacyFoundRowsForm extends CRM_Report_Form_Contribute_Detail {

  /**
   * @inheritDoc
   */
  public function buildQuery($applyLimit = FALSE) {
    $sql = preg_replace('/^\s*SELECT\b/i', 'SELECT SQL_CALC_FOUND_ROWS', parent::buildQuery($applyLimit));
    if ($this->isTempTableBuilt) {
      $sql = preg_replace('/^SELECT SQL_CALC_FOUND_ROWS \*/', 'SELECT SQL_CALC_FOUND_ROWS *, 1 AS legacy_column, 1 AS legacy_column', $sql, 1, $replaced);
      if ($replaced !== 1) {
        throw new RuntimeException('Displayed query has an unexpected form.');
      }
    }
    return $sql;
  }

}
