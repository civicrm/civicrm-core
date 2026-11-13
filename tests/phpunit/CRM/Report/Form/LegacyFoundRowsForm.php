<?php

/**
 * Report as written by extensions that still request SQL_CALC_FOUND_ROWS.
 */
class CRM_Report_Form_LegacyFoundRowsForm extends CRM_Report_Form_Contribute_Detail {

  /**
   * @inheritDoc
   */
  public function buildQuery($applyLimit = FALSE) {
    return preg_replace('/^\s*SELECT\b/i', 'SELECT SQL_CALC_FOUND_ROWS', parent::buildQuery($applyLimit));
  }

}
