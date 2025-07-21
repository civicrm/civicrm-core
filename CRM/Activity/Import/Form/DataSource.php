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

use Civi\Import\ActivityParser;

/**
 * This class gets the name of the file to upload.
 */
class CRM_Activity_Import_Form_DataSource extends CRM_CiviImport_Form_DataSource {

  /**
   * Should the text describing date formats include the time.
   *
   * This is used to alter the displayed text to that perceived to be more useful.
   * For activities it is likely the user wants to know how to format time.
   *
   * @var bool
   */
  protected $isDisplayTimeInDateFormats = TRUE;

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
   * @return \Civi\Import\ActivityParser
   */
  protected function getParser(): ActivityParser {
    if (!$this->parser) {
      $this->parser = new ActivityParser();
      $this->parser->setUserJobID($this->getUserJobID());
      $this->parser->init();
    }
    return $this->parser;
  }

}
