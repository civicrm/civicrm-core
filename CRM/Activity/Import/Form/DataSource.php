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
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    // FIXME: This 'onDuplicate' form element is never used -- copy/paste error?
    $duplicateOptions = [];
    $duplicateOptions[] = $this->createElement('radio',
      NULL, NULL, ts('Skip'), CRM_Import_Parser::DUPLICATE_SKIP
    );
    $duplicateOptions[] = $this->createElement('radio',
      NULL, NULL, ts('Update'), CRM_Import_Parser::DUPLICATE_UPDATE
    );
    $duplicateOptions[] = $this->createElement('radio',
      NULL, NULL, ts('Fill'), CRM_Import_Parser::DUPLICATE_FILL
    );

    $this->addGroup($duplicateOptions, 'onDuplicate',
      ts('On duplicate entries')
    );
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
