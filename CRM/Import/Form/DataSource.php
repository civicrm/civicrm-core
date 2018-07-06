<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Base class for upload-only import forms (all but Contact import).
 */
abstract class CRM_Import_Form_DataSource extends CRM_Core_Form {

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
    $params = "reset=1";
    if ($this->_id) {
      $params .= "&id={$this->_id}";
    }
    CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url(static::PATH, $params));

    // check for post max size
    CRM_Utils_Number::formatUnitSize(ini_get('post_max_size'), TRUE);
  }

  /**
   * Common form elements.
   */
  public function buildQuickForm() {
    $config = CRM_Core_Config::singleton();

    $uploadFileSize = CRM_Utils_Number::formatUnitSize($config->maxFileSize . 'm', TRUE);

    //Fetch uploadFileSize from php_ini when $config->maxFileSize is set to "no limit".
    if (empty($uploadFileSize)) {
      $uploadFileSize = CRM_Utils_Number::formatUnitSize(ini_get('upload_max_filesize'), TRUE);
    }
    $uploadSize = round(($uploadFileSize / (1024 * 1024)), 2);

    $this->assign('uploadSize', $uploadSize);

    $this->add('File', 'uploadFile', ts('Import Data File'), 'size=30 maxlength=255', TRUE);
    $this->setMaxFileSize($uploadFileSize);
    $this->addRule('uploadFile', ts('File size should be less than %1 MBytes (%2 bytes)', array(
      1 => $uploadSize,
      2 => $uploadFileSize,
    )), 'maxfilesize', $uploadFileSize);
    $this->addRule('uploadFile', ts('A valid file must be uploaded.'), 'uploadedfile');
    $this->addRule('uploadFile', ts('Input file must be in CSV format'), 'utf8File');

    $this->addElement('checkbox', 'skipColumnHeader', ts('First row contains column headers'));

    $this->add('text', 'fieldSeparator', ts('Import Field Separator'), array('size' => 2), TRUE);
    $this->setDefaults(array('fieldSeparator' => $config->fieldSeparator));
    $mappingArray = CRM_Core_BAO_Mapping::getCreateMappingValues('Import ' . static::IMPORT_ENTITY);

    $this->assign('savedMapping', $mappingArray);
    $this->add('select', 'savedMapping', ts('Mapping Option'), array('' => ts('- select -')) + $mappingArray);

    if ($loadedMapping = $this->get('loadedMapping')) {
      $this->assign('loadedMapping', $loadedMapping);
      $this->setDefaults(array('savedMapping' => $loadedMapping));
    }

    //build date formats
    CRM_Core_Form_Date::buildAllowedDateFormats($this);

    $this->addButtons(array(
        array(
          'type' => 'upload',
          'name' => ts('Continue'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   * A long-winded way to add one radio element to the form.
   */
  protected function addContactTypeSelector() {
    //contact types option
    $contactOptions = array();
    if (CRM_Contact_BAO_ContactType::isActive('Individual')) {
      $contactOptions[] = $this->createElement('radio',
        NULL, NULL, ts('Individual'), CRM_Import_Parser::CONTACT_INDIVIDUAL
      );
    }
    if (CRM_Contact_BAO_ContactType::isActive('Household')) {
      $contactOptions[] = $this->createElement('radio',
        NULL, NULL, ts('Household'), CRM_Import_Parser::CONTACT_HOUSEHOLD
      );
    }
    if (CRM_Contact_BAO_ContactType::isActive('Organization')) {
      $contactOptions[] = $this->createElement('radio',
        NULL, NULL, ts('Organization'), CRM_Import_Parser::CONTACT_ORGANIZATION
      );
    }

    $this->addGroup($contactOptions, 'contactType',
      ts('Contact Type')
    );

    $this->setDefaults(array(
      'contactType' => CRM_Import_Parser::CONTACT_INDIVIDUAL,
    ));
  }

  /**
   * Store form values.
   *
   * @param array $names
   */
  protected function storeFormValues($names) {
    foreach ($names as $name) {
      $this->set($name, $this->controller->exportValue($this->_name, $name));
    }
  }

  /**
   * Common form postProcess.
   *
   * @param string $parserClassName
   *
   * @param string|null $entity
   *   Entity to set for paraser currently only for custom import
   */
  protected function submitFileForMapping($parserClassName, $entity = NULL) {
    $this->controller->resetPage('MapField');

    $fileName = $this->controller->exportValue($this->_name, 'uploadFile');
    $skipColumnHeader = $this->controller->exportValue($this->_name, 'skipColumnHeader');

    $session = CRM_Core_Session::singleton();
    $session->set("dateTypes", $this->get('dateFormats'));

    $separator = $this->controller->exportValue($this->_name, 'fieldSeparator');

    $mapper = array();

    $parser = new $parserClassName($mapper);
    if ($entity) {
      $parser->setEntity($this->get($entity));
    }
    $parser->setMaxLinesToProcess(100);
    $parser->run($fileName,
      $separator,
      $mapper,
      $skipColumnHeader,
      CRM_Import_Parser::MODE_MAPFIELD,
      $this->get('contactType')
    );

    // add all the necessary variables to the form
    $parser->set($this);
  }

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string
   */
  public function getTitle() {
    return ts('Upload Data');
  }

}
