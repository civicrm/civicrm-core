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

use Civi\Api4\Mapping;
use Civi\Api4\Utils\CoreUtil;
use Civi\Api4\UserJob;

/**
 * Base class for upload-only import forms (all but Contact import).
 */
abstract class CRM_Import_Form_DataSource extends CRM_Import_Forms {

  /**
   * Should the text describing date formats include the time.
   *
   * This is used to alter the displayed text to that perceived to be more useful.
   * e.g. for contacts it might be birthdate so including time is confusing
   * but activities would more likely use them.
   *
   * @var bool
   */
  protected $isDisplayTimeInDateFormats = FALSE;

  /**
   * Values loaded from a saved UserJob template.
   *
   * Within Civi-Import it is possible to save a UserJob with is_template = 1.
   *
   * @var array
   */
  protected $templateValues = [];

  public function getTemplateFileName(): string {
    return 'CRM/Import/Form/DataSource.tpl';
  }

  /**
   * Set variables up before form is built.
   */
  public function preProcess(): void {
    $this->pushUrlToUserContext();
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
    return (string) CoreUtil::getInfoItem($this->getBaseEntity(), 'title');
  }

  /**
   * Get the mapping ID that is being loaded.
   *
   * @return int|null
   * @throws \CRM_Core_Exception
   */
  public function getSavedMappingID(): ?int {
    return $this->getSubmittedValue('savedMapping') ?: NULL;
  }

  /**
   * Get the import entity plural (translated).
   *
   * Used for template layer text.
   *
   * @return string
   */
  protected function getTranslatedEntities(): string {
    return (string) CoreUtil::getInfoItem($this->getBaseEntity(), 'title_plural');
  }

  /**
   * Common form elements.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    $this->assign('errorMessage', $this->getErrorMessage());

    $this->assign('urlPath', 'civicrm/import/datasource');
    $this->assign('urlPathVar', 'snippet=4&user_job_id=' . $this->get('user_job_id'));
    if ($this->isImportDataUploaded()) {
      $this->add('checkbox', 'use_existing_upload', ts('Use data already uploaded'), [
        'onChange' => "
          CRM.$('.crm-import-datasource-form-block-dataSource').toggle();
          CRM.$('#data-source-form-block').toggle()",
      ]);
    }
    if ($this->getTemplateID()) {
      $this->setTemplateDefaults();
    }

    $this->add('select', 'dataSource', ts('Data Source'), $this->getDataSources(), TRUE,
      ['onchange' => 'buildDataSourceFormBlock(this.value);']
    );

    $mappingArray = CRM_Core_BAO_Mapping::getCreateMappingValues('Import ' . $this->getBaseEntity());

    $savedMappingElement = $this->add('select', 'savedMapping', ts('Saved Field Mapping'), ['' => ts('- select -')] + $mappingArray);
    if ($this->getTemplateID()) {
      $savedMappingElement->freeze();
    }

    //build date formats
    $this->buildAllowedDateFormats();
    // When we call buildDataSourceFields we add them to the form both for purposes of
    // initial display, but also so they are available during `postProcess`. Hence
    // we need to add them to the form when first displaying it, or when a csv has been
    // uploaded or csv described but NOT when the existing file is used. We have
    // to check `_POST` for this because we want them to be not-added BEFORE validation
    // as `buildDataSourceFields` also adds rules, which will run before `use_existing_upload`
    // is treated as submitted.
    if (empty($_POST['use_existing_upload'])) {
      $this->buildDataSourceFields();
    }
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
   * Build the date-format form.
   */
  protected function buildAllowedDateFormats(): void {
    $formats = CRM_Utils_Date::getAvailableInputFormats($this->isDisplayTimeInDateFormats);
    $this->addRadio('dateFormats', ts('Date Format'), $formats, [], '<br/>');
    $this->setDefaults(['dateFormats' => array_key_first($formats)]);
  }

  public function setDefaultValues() {
    return array_merge($this->dataSourceDefaults, [
      'dataSource' => $this->getDefaultDataSource(),
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP,
    ], $this->templateValues);
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
  protected function storeFormValues(array $names): void {
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
   * Load default values from the relevant template if one is passed in via the url.
   *
   * We need to create and UserJob at this point as the relevant values
   * go beyond the first DataSource screen.
   *
   * @return array
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function setTemplateDefaults(): array {
    $templateID = $this->getTemplateID();
    if ($templateID && !$this->getUserJobID()) {
      $userJob = UserJob::get(FALSE)->addWhere('id', '=', $templateID)->execute()->first();
      $userJobName = $userJob['name'];
      // Strip off import_ prefix from UserJob.name
      $mappingName = substr($userJobName, 7);
      // This mapping is deprecated but still used for Contact, Activity.
      $mappingID = Mapping::get(FALSE)->addWhere('name', '=', $mappingName)->addSelect('id')->execute()->first()['id'] ?? NULL;
      // Unset fields that should not be copied over.
      unset($userJob['id'], $userJob['name'], $userJob['created_date'], $userJob['is_template'], $userJob['queue_id'], $userJob['start_date'], $userJob['end_date']);
      $userJob['metadata']['template_id'] = $templateID;
      $userJob['metadata']['Template']['mapping_id'] = $mappingID;
      $userJob['created_id'] = CRM_Core_Session::getLoggedInContactID();
      $userJob['expires_date'] = '+1 week';
      $userJobID = UserJob::create(FALSE)->setValues($userJob)->execute()->first()['id'];
      $this->set('user_job_id', $userJobID);
      $userJob['metadata']['submitted_values']['savedMapping'] = $mappingID;
      $this->templateValues = $userJob['metadata']['submitted_values'];
    }
    return [];
  }

  /**
   * Process the datasource submission - setting up the job and data source.
   */
  protected function processDatasource(): void {
    try {
      if (!$this->getUserJobID()) {
        $this->createUserJob();
        $this->instantiateDataSource();
      }
      else {
        $submittedValues = $this->getSubmittedValues();
        $fieldsToCopyOver = array_keys(array_diff_key($submittedValues, $this->submittableFields));
        if ($submittedValues['use_existing_upload']) {
          // Use the already saved value.
          $fieldsToCopyOver[] = 'dataSource';
          foreach ($fieldsToCopyOver as $field) {
            $submittedValues[$field] = $this->getUserJobSubmittedValues()[$field];
          }
          $this->updateUserJobMetadata('submitted_values', $submittedValues);
        }
        else {
          $this->flushDataSource();
          $this->updateUserJobMetadata('submitted_values', $submittedValues);
          $this->instantiateDataSource();
        }
      }
    }
    catch (CRM_Core_Exception $e) {
      CRM_Core_Error::statusBounce($e->getUserMessage());
    }
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

  /**
   * Default values for datasource fields.
   *
   * @var array
   */
  protected $dataSourceDefaults = [];

  /**
   * Set dataSource default values.
   *
   * @param array $dataSourceDefaults
   *
   * @return self
   */
  public function setDataSourceDefaults(array $dataSourceDefaults): self {
    $this->dataSourceDefaults = $dataSourceDefaults;
    return $this;
  }

}
