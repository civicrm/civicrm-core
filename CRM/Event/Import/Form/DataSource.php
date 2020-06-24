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
 * $Id$
 *
 */

/**
 * This class gets the name of the file to upload
 */
class CRM_Event_Import_Form_DataSource extends CRM_Import_Form_DataSource {

  const PATH = 'civicrm/event/import';

  const IMPORT_ENTITY = 'Participant';

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    $duplicateOptions = [];
    $duplicateOptions[] = $this->createElement('radio',
      NULL, NULL, ts('Skip'), CRM_Import_Parser::DUPLICATE_SKIP
    );
    $duplicateOptions[] = $this->createElement('radio',
      NULL, NULL, ts('Update'), CRM_Import_Parser::DUPLICATE_UPDATE
    );
    $duplicateOptions[] = $this->createElement('radio',
      NULL, NULL, ts('No Duplicate Checking'), CRM_Import_Parser::DUPLICATE_NOCHECK
    );
    $this->addGroup($duplicateOptions, 'onDuplicate',
      ts('On Duplicate Entries')
    );

    $this->setDefaults(['onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP]);

    $this->addContactTypeSelector();
  }

  /**
   * Process the uploaded file.
   *
   * @return void
   */
  public function postProcess() {
    $this->storeFormValues([
      'onDuplicate',
      'contactType',
      'dateFormats',
      'savedMapping',
    ]);

    $this->submitFileForMapping('CRM_Event_Import_Parser_Participant');
  }

}
