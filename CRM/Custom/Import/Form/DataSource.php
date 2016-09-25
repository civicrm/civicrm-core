<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */

/**
 * This class gets the name of the file to upload
 */
class CRM_Custom_Import_Form_DataSource extends CRM_Import_Form_DataSource {

  const PATH = 'civicrm/import/custom';

  const IMPORT_ENTITY = 'Multi value custom data';

  /**
   * @return array
   */
  public function setDefaultValues() {
    $config = CRM_Core_Config::singleton();
    $defaults = array(
      'contactType' => CRM_Import_Parser::CONTACT_INDIVIDUAL,
      'fieldSeparator' => $config->fieldSeparator,
      'multipleCustomData' => $this->_id,
    );

    if ($loadeMapping = $this->get('loadedMapping')) {
      $this->assign('loadedMapping', $loadeMapping);
      $defaults['savedMapping'] = $loadeMapping;
    }

    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    $multipleCustomData = CRM_Core_BAO_CustomGroup::getMultipleFieldGroup();
    $this->add('select', 'multipleCustomData', ts('Multi-value Custom Data'), array('' => ts('- select -')) + $multipleCustomData, TRUE);

    $this->addContactTypeSelector();
  }

  /**
   * Process the uploaded file.
   *
   * @return void
   */
  public function postProcess() {
    $this->storeFormValues(array(
      'contactType',
      'dateFormats',
      'savedMapping',
      'multipleCustomData',
    ));

    $this->submitFileForMapping('CRM_Custom_Import_Parser_Api', 'multipleCustomData');
  }

}
