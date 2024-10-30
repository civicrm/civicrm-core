<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * A literal fragment of SQL.
 *
 * Ex: new CRM_Utils_SQL_Literal('CONCAT(@foo, sysdate())')
 */
class CRM_Utils_SQL_Literal {

  /**
   * @var string
   */
  private $value;

  /**
   * @param string $value
   */
  public function __construct(string $value) {
    $this->value = $value;
  }

  /**
   * @return string
   */
  public function getValue() {
    return $this->value;
  }

}
