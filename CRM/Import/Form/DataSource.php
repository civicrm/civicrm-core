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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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

    $this->add('File', 'uploadFile', ts('Import Data File'), NULL, TRUE);
    $this->setMaxFileSize($uploadFileSize);
    $this->addRule('uploadFile', ts('File size should be less than %1 MBytes (%2 bytes)', [
      1 => $uploadSize,
      2 => $uploadFileSize,
    ]), 'maxfilesize', $uploadFileSize);
    $this->addRule('uploadFile', ts('A valid file must be uploaded.'), 'uploadedfile');
    $this->addRule('uploadFile', ts('Input file must be in CSV format'), 'utf8File');

    $this->addElement('checkbox', 'skipColumnHeader', ts('First row contains column headers'));

    $this->add('text', 'fieldSeparator', ts('Import Field Separator'), ['size' => 2], TRUE);
    $this->setDefaults(['fieldSeparator' => $config->fieldSeparator]);
    $mappingArray = CRM_Core_BAO_Mapping::getCreateMappingValues('Import ' . static::IMPORT_ENTITY);

    $this->assign('savedMapping', $mappingArray);
    $this->add('select', 'savedMapping', ts('Mapping Option'), ['' => ts('- select -')] + $mappingArray);

    if ($loadedMapping = $this->get('loadedMapping')) {
      $this->assign('loadedMapping', $loadedMapping);
      $this->setDefaults(['savedMapping' => $loadedMapping]);
    }

    //build date formats
    CRM_Core_Form_Date::buildAllowedDateFormats($this);

    $this->addButtons([
        [
          'type' => 'upload',
          'name' => ts('Continue'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
    ]);
  }

  /**
   * A long-winded way to add one radio element to the form.
   */
  protected function addContactTypeSelector() {
    //contact types option
    $contactTypeOptions = [];
    if (CRM_Contact_BAO_ContactType::isActive('Individual')) {
      $contactTypeOptions[CRM_Import_Parser::CONTACT_INDIVIDUAL] = ts('Individual');
    }
    if (CRM_Contact_BAO_ContactType::isActive('Household')) {
      $contactTypeOptions[CRM_Import_Parser::CONTACT_HOUSEHOLD] = ts('Household');
    }
    if (CRM_Contact_BAO_ContactType::isActive('Organization')) {
      $contactTypeOptions[CRM_Import_Parser::CONTACT_ORGANIZATION] = ts('Organization');
    }
    $this->addRadio('contactType', ts('Contact Type'), $contactTypeOptions);

    $this->setDefaults([
      'contactType' => CRM_Import_Parser::CONTACT_INDIVIDUAL,
    ]);
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

    $mapper = [];

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
