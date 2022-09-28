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
   * @var bool
   */
  public $submitOnce = TRUE;

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    // FIXME: This 'onDuplicate' form element is never used -- copy/paste error?
    $this->addRadio('onDuplicate', ts('On duplicate entries'), [
      CRM_Import_Parser::DUPLICATE_SKIP => ts('Skip'),
      CRM_Import_Parser::DUPLICATE_UPDATE => ts('Update'),
      CRM_Import_Parser::DUPLICATE_FILL => ts('Fill'),
    ]);
  }

  /**
   * Process the uploaded file.
   */
  public function postProcess() {
    $this->storeFormValues([
      'onDuplicate',
      'dateFormats',
      'savedMapping',
    ]);

    $this->submitFileForMapping('CRM_Activity_Import_Parser_Activity');
  }

}
