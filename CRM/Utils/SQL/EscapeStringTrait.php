<?php

trait CRM_Utils_SQL_EscapeStringTrait {

  protected $allowLiterals = FALSE;

  public function allowLiterals(bool $allowLiterals = TRUE) {
    $this->allowLiterals = $allowLiterals;
    return $this;
  }

  /**
   * @param string|null $value
   * @return string
   *   SQL expression, e.g. "it\'s great" (with-quotes) or NULL (without-quotes)
   */
  public function escapeString($value) {
    if ($value === NULL) {
      return 'NULL';
    }
    if ($value instanceof CRM_Utils_SQL_Literal) {
      if ($this->allowLiterals) {
        return $value->getValue();
      }
      else {
        throw new CRM_Core_Exception('SQL builder does not support literal expressions. Must call allowLiterals() first.');
      }
    }

    if (!isset($GLOBALS['CIVICRM_SQL_ESCAPER'])) {
      return '"' . CRM_Core_DAO::escapeString($value) . '"';
    }
    else {
      return '"' . call_user_func($GLOBALS['CIVICRM_SQL_ESCAPER'], $value) . '"';
    }

  }

}
