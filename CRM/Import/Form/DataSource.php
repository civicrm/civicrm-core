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
abstract class CRM_Import_Form_DataSource extends CRM_Import_Forms {

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    // check for post max size
    CRM_Utils_Number::formatUnitSize(ini_get('post_max_size'), TRUE);
    $this->assign('importEntity', $this->getTranslatedEntity());
    $this->assign('importEntities', $this->getTranslatedEntities());
  }

  /**
   * Get the import entity (translated).
   *
   * Used for template layer text.
   *
   * @return string
   */
  protected function getTranslatedEntity(): string {
    return (string) Civi\Api4\Utils\CoreUtil::getInfoItem($this::IMPORT_ENTITY, 'title');
  }

  /**
   * Get the import entity plural (translated).
   *
   * Used for template layer text.
   *
   * @return string
   */
  protected function getTranslatedEntities(): string {
    return (string) Civi\Api4\Utils\CoreUtil::getInfoItem($this::IMPORT_ENTITY, 'title_plural');
  }

  /**
   * Common form elements.
   */
  public function buildQuickForm() {
    $this->assign('errorMessage', $this->getErrorMessage());
    $config = CRM_Core_Config::singleton();
    // When we switch to using the DataSource.tpl used by Contact we can remove this in
    // favour of the one used by Contact - I was trying to consolidate
    // first & got stuck on https://github.com/civicrm/civicrm-core/pull/23458
    $this->add('hidden', 'hidden_dataSource', 'CRM_Import_DataSource_CSV');
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
    $this->add('select', 'savedMapping', ts('Saved Field Mapping'), ['' => ts('- select -')] + $mappingArray);

    if ($loadedMapping = $this->get('loadedMapping')) {
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
   * Get an error message to assign to the template.
   *
   * @return string
   */
  protected function getErrorMessage(): string {
    return '';
  }

  /**
   * A long-winded way to add one radio element to the form.
   */
  protected function addContactTypeSelector() {
    //contact types option
    $contactTypeOptions = [];
    if (CRM_Contact_BAO_ContactType::isActive('Individual')) {
      $contactTypeOptions['Individual'] = ts('Individual');
    }
    if (CRM_Contact_BAO_ContactType::isActive('Household')) {
      $contactTypeOptions['Household'] = ts('Household');
    }
    if (CRM_Contact_BAO_ContactType::isActive('Organization')) {
      $contactTypeOptions['Organization'] = ts('Organization');
    }
    $this->addRadio('contactType', ts('Contact Type'), $contactTypeOptions);

    $this->setDefaults([
      'contactType' => 'Individual',
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
   * Common postProcessing.
   */
  public function postProcess() {
    $this->processDatasource();
    $this->controller->resetPage('MapField');
    parent::postProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string
   */
  public function getTitle() {
    return ts('Upload Data');
  }

  /**
   * Process the datasource submission - setting up the job and data source.
   *
   * @throws \CRM_Core_Exception
   */
  protected function processDatasource(): void {
    if (!$this->getUserJobID()) {
      $this->createUserJob();
    }
    else {
      $this->flushDataSource();
      $this->updateUserJobMetadata('submitted_values', $this->getSubmittedValues());
    }
    $this->instantiateDataSource();
  }

  /**
   * Instantiate the datasource.
   *
   * This gives the datasource a chance to do any table creation etc.
   *
   * @throws \CRM_Core_Exception
   */
  private function instantiateDataSource(): void {
    $this->getDataSourceObject()->initialize();
  }

}
