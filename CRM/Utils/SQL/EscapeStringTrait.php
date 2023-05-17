<?php

trait CRM_Utils_SQL_EscapeStringTrait {

  /**
   * @param string|null $value
   * @return string
   *   SQL expression, e.g. "it\'s great" (with-quotes) or NULL (without-quotes)
   */
  public function escapeString($value) {
    return $value === NULL ? 'NULL' : '"' . CRM_Core_DAO::escapeString($value) . '"';
  }

}
