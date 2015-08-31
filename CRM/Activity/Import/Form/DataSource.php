<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
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
    $duplicateOptions = array();
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
    $this->storeFormValues(array(
      'onDuplicate',
      'dateFormats',
      'savedMapping',
    ));

    $this->submitFileForMapping('CRM_Activity_Import_Parser_Activity');
  }

}
