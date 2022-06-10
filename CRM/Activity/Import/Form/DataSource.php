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

/**
 * This class gets the name of the file to upload.
 */
class CRM_Activity_Import_Form_DataSource extends CRM_Import_Form_DataSource {

  const PATH = 'civicrm/import/activity';

  const IMPORT_ENTITY = 'Activity';

  /**
   * Get the name of the type to be stored in civicrm_user_job.type_id.
   *
   * @return string
   */
  public function getUserJobType(): string {
    return 'activity_import';
  }

  /**
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
  }

  /**
   * @return CRM_Activity_Import_Parser_Activity
   */
  protected function getParser(): CRM_Activity_Import_Parser_Activity {
    if (!$this->parser) {
      $this->parser = new CRM_Activity_Import_Parser_Activity();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

}
