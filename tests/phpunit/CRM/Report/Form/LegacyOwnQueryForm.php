<?php

/**
 * Report as written by extensions that run their own SQL_CALC_FOUND_ROWS query instead of buildRows().
 */
class CRM_Report_Form_LegacyOwnQueryForm extends CRM_Report_Form_Member_Lapse {

  /**
   * @inheritDoc
   */
  public function postProcess() {
    $this->beginPostProcess();
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $sql = preg_replace('/^\s*SELECT\b/i', 'SELECT SQL_CALC_FOUND_ROWS', $this->buildQuery(TRUE));
    $dao = CRM_Core_DAO::executeQuery($sql);
    $rows = [];
    while ($dao->fetch()) {
      $row = [];
      foreach ($this->_columnHeaders as $key => $value) {
        $row[$key] = $dao->$key;
      }
      $rows[] = $row;
    }
    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

}
