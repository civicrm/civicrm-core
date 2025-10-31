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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Import\MembershipParser;

/**
 * This class previews the uploaded file and returns summary
 * statistics
 */
class CRM_Member_Import_Form_Preview extends CRM_Import_Form_Preview {

  /**
   * Set variables up before form is built.
   *
   * @return void
   */
  public function preProcess(): void {
    parent::preProcess();
    $this->setStatusUrl();
  }

  /**
   * @return \Civi\Import\MembershipParser
   */
  protected function getParser(): MembershipParser {
    if (!$this->parser) {
      $this->parser = new MembershipParser();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

}
