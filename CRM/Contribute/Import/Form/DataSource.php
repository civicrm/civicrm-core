<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
