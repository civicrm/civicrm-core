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

use Civi\Import\ParticipantParser;

/**
 * This class gets the name of the file to upload
 */
class CRM_Event_Import_Form_DataSource extends CRM_CiviImport_Form_DataSource {

  /**
   * Get the name of the type to be stored in civicrm_user_job.type_id.
   *
   * @return string
   */
  public function getUserJobType(): string {
    return 'participant_import';
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    $this->addRadio('onDuplicate', ts('On Duplicate Entries'), [
      CRM_Import_Parser::DUPLICATE_SKIP => ts('Skip'),
      CRM_Import_Parser::DUPLICATE_UPDATE => ts('Update'),
      CRM_Import_Parser::DUPLICATE_NOCHECK => ts('No Duplicate Checking'),
    ]);
  }

  /**
   * @return \Civi\Import\ParticipantParser
   */
  protected function getParser(): ParticipantParser {
    if (!$this->parser) {
      $this->parser = new ParticipantParser();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

}
