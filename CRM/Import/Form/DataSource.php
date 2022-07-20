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
   * Common postProcessing.
   */
  public function postProcess() {
    $this->processDatasource();
    $this->controller->resetPage('MapField');
    parent::postProcess();
  }

  /**
   * Common form postProcess.
   * @deprecated - just use postProcess.
   *
   * @param string $parserClassName
   * @param string|null $entity
   *   Entity to set for paraser currently only for custom import
   */
  protected function submitFileForMapping($parserClassName, $entity = NULL) {
    CRM_Core_Session::singleton()->set('dateTypes', $this->getSubmittedValue('dateFormats'));
    $this->processDatasource();

    $mapper = [];

    $parser = new $parserClassName($mapper);
    if ($entity) {
      $parser->setEntity($this->get($entity));
    }
    $parser->setMaxLinesToProcess(100);
    $parser->setUserJobID($this->getUserJobID());
    $parser->run(
      $this->getSubmittedValue('uploadFile'),
      $this->getSubmittedValue('fieldSeparator'),
      [],
      $this->getSubmittedValue('skipColumnHeader'),
      CRM_Import_Parser::MODE_MAPFIELD,
      $this->getSubmittedValue('contactType')
    );

    // add all the necessary variables to the form
    $parser->set($this);
    $this->controller->resetPage('MapField');
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
   * @throws \API_Exception
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
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  private function instantiateDataSource(): void {
    $this->getDataSourceObject()->initialize();
  }

}
