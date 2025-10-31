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

use Civi\Import\MembershipParser;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class gets the name of the file to upload
 */
class CRM_Member_Import_Form_DataSource extends CRM_CiviImport_Form_DataSource {

  /**
   * Get the name of the type to be stored in civicrm_user_job.type_id.
   *
   * @return string
   */
  public function getUserJobType(): string {
    return 'membership_import';
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    $this->addRadio('onDuplicate', ts('Import mode'), [
      CRM_Import_Parser::DUPLICATE_SKIP => ts('Insert new Membership'),
      CRM_Import_Parser::DUPLICATE_UPDATE => ts('Update existing Membership'),
    ]);
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
