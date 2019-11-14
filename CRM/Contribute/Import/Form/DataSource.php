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
 * This class gets the name of the file to upload
 */
class CRM_Contribute_Import_Form_DataSource extends CRM_Import_Form_DataSource {

  const PATH = 'civicrm/contribute/import';

  const IMPORT_ENTITY = 'Contribution';

  /**
   * Build the form object.
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    $duplicateOptions = [];
    $duplicateOptions[] = $this->createElement('radio',
      NULL, NULL, ts('Insert new contributions'), CRM_Import_Parser::DUPLICATE_SKIP
    );
    $duplicateOptions[] = $this->createElement('radio',
      NULL, NULL, ts('Update existing contributions'), CRM_Import_Parser::DUPLICATE_UPDATE
    );
    $this->addGroup($duplicateOptions, 'onDuplicate',
      ts('Import mode')
    );

    $this->setDefaults(['onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP]);

    $this->addElement('submit', 'loadMapping', ts('Load Mapping'), NULL, ['onclick' => 'checkSelect()']);

    $this->addContactTypeSelector();
  }

  /**
   * Process the uploaded file.
   */
  public function postProcess() {
    $this->storeFormValues([
      'onDuplicate',
      'contactType',
      'dateFormats',
      'savedMapping',
    ]);

    $this->submitFileForMapping('CRM_Contribute_Import_Parser_Contribution');
  }

}
