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
class CRM_Custom_Import_Form_DataSource extends CRM_Import_Form_DataSource {

  const PATH = 'civicrm/import/custom';

  const IMPORT_ENTITY = 'Multi value custom data';

  /**
   * @return array
   */
  public function setDefaultValues() {
    $config = CRM_Core_Config::singleton();
    $defaults = [
      'contactType' => CRM_Import_Parser::CONTACT_INDIVIDUAL,
      'fieldSeparator' => $config->fieldSeparator,
      'multipleCustomData' => $this->_id,
    ];

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
    $this->add('select', 'multipleCustomData', ts('Multi-value Custom Data'), ['' => ts('- select -')] + $multipleCustomData, TRUE);

    $this->addContactTypeSelector();
  }

  /**
   * Process the uploaded file.
   *
   * @return void
   */
  public function postProcess() {
    $this->storeFormValues([
      'contactType',
      'dateFormats',
      'savedMapping',
      'multipleCustomData',
    ]);

    $this->submitFileForMapping('CRM_Custom_Import_Parser_Api', 'multipleCustomData');
  }

}
