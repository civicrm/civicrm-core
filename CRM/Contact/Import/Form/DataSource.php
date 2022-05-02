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
 * This class delegates to the chosen DataSource to grab the data to be imported.
 */
class CRM_Contact_Import_Form_DataSource extends CRM_Import_Forms {

  /**
   * Get any smarty elements that may not be present in the form.
   *
   * To make life simpler for smarty we ensure they are set to null
   * rather than unset. This is done at the last minute when $this
   * is converted to an array to be assigned to the form.
   *
   * @return array
   */
  public function getOptionalQuickFormElements(): array {
    return ['disableUSPS'];
  }

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    $results = [];
    $config = CRM_Core_Config::singleton();
    $handler = opendir($config->uploadDir);
    $errorFiles = ['sqlImport.errors', 'sqlImport.conflicts', 'sqlImport.duplicates', 'sqlImport.mismatch'];

    // check for post max size avoid when called twice
    $snippet = $_GET['snippet'] ?? 0;
    if (empty($snippet)) {
      CRM_Utils_Number::formatUnitSize(ini_get('post_max_size'), TRUE);
    }

    while ($file = readdir($handler)) {
      if ($file !== '.' && $file !== '..' &&
        in_array($file, $errorFiles) && !is_writable($config->uploadDir . $file)
      ) {
        $results[] = $file;
      }
    }
    closedir($handler);
    if (!empty($results)) {
      $this->invalidConfig(ts('<b>%1</b> file(s) in %2 directory are not writable. Listed file(s) might be used during the import to log the errors occurred during Import process. Contact your site administrator for assistance.', [
        1 => implode(', ', $results),
        2 => $config->uploadDir,
      ]));
    }
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {

    $this->assign('urlPath', 'civicrm/import/datasource');
    $this->assign('urlPathVar', 'snippet=4&user_job_id=' . $this->get('user_job_id'));

    $this->add('select', 'dataSource', ts('Data Source'), $this->getDataSources(), TRUE,
      ['onchange' => 'buildDataSourceFormBlock(this.value);']
    );

    // duplicate handling options
    $this->addRadio('onDuplicate', ts('For Duplicate Contacts'), [
      CRM_Import_Parser::DUPLICATE_SKIP => ts('Skip'),
      CRM_Import_Parser::DUPLICATE_UPDATE => ts('Update'),
      CRM_Import_Parser::DUPLICATE_FILL => ts('Fill'),
      CRM_Import_Parser::DUPLICATE_NOCHECK => ts('No Duplicate Checking'),
    ]);

    $mappingArray = CRM_Core_BAO_Mapping::getMappings('Import Contact');

    $this->assign('savedMapping', $mappingArray);
    $this->addElement('select', 'savedMapping', ts('Saved Field Mapping'), ['' => ts('- select -')] + $mappingArray);

    $js = ['onClick' => "buildSubTypes();buildDedupeRules();"];
    // contact types option
    $contactTypeOptions = $contactTypeAttributes = [];
    if (CRM_Contact_BAO_ContactType::isActive('Individual')) {
      $contactTypeOptions[CRM_Import_Parser::CONTACT_INDIVIDUAL] = ts('Individual');
      $contactTypeAttributes[CRM_Import_Parser::CONTACT_INDIVIDUAL] = $js;
    }
    if (CRM_Contact_BAO_ContactType::isActive('Household')) {
      $contactTypeOptions[CRM_Import_Parser::CONTACT_HOUSEHOLD] = ts('Household');
      $contactTypeAttributes[CRM_Import_Parser::CONTACT_HOUSEHOLD] = $js;
    }
    if (CRM_Contact_BAO_ContactType::isActive('Organization')) {
      $contactTypeOptions[CRM_Import_Parser::CONTACT_ORGANIZATION] = ts('Organization');
      $contactTypeAttributes[CRM_Import_Parser::CONTACT_ORGANIZATION] = $js;
    }
    $this->addRadio('contactType', ts('Contact Type'), $contactTypeOptions, [], NULL, FALSE, $contactTypeAttributes);

    $this->addElement('select', 'subType', ts('Subtype'));
    $this->addElement('select', 'dedupe_rule_id', ts('Dedupe Rule'));

    CRM_Core_Form_Date::buildAllowedDateFormats($this);

    $geoCode = FALSE;
    if (CRM_Utils_GeocodeProvider::getUsableClassName()) {
      $geoCode = TRUE;
      $this->addElement('checkbox', 'doGeocodeAddress', ts('Geocode addresses during import?'));
    }
    $this->assign('geoCode', $geoCode);

    $this->addElement('text', 'fieldSeparator', ts('Import Field Separator'), ['size' => 2]);

    if (Civi::settings()->get('address_standardization_provider') === 'USPS') {
      $this->addElement('checkbox', 'disableUSPS', ts('Disable USPS address validation during import?'));
    }
    $this->buildDataSourceFields();

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
   * Set the default values of various form elements.
   *
   * @return array
   *   reference to the array of default values
   */
  public function setDefaultValues() {
    $defaults = [
      'dataSource' => $this->getDefaultDataSource(),
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP,
      'contactType' => CRM_Import_Parser::CONTACT_INDIVIDUAL,
      'fieldSeparator' => CRM_Core_Config::singleton()->fieldSeparator,
    ];

    if ($this->get('loadedMapping')) {
      $defaults['savedMapping'] = $this->get('loadedMapping');
    }

    return $defaults;
  }

  /**
   * Call the DataSource's postProcess method.
   *
   * @throws \CRM_Core_Exception
   * @throws \API_Exception
   */
  public function postProcess() {
    $this->controller->resetPage('MapField');
    if (!$this->getUserJobID()) {
      $this->createUserJob();
    }
    else {
      $this->flushDataSource();
      $this->updateUserJobMetadata('submitted_values', $this->getSubmittedValues());
    }
    // Setup the params array
    $this->_params = $this->controller->exportValues($this->_name);

    $storeParams = [
      'onDuplicate' => $this->getSubmittedValue('onDuplicate'),
      'dedupe' => $this->getSubmittedValue('dedupe_rule_id'),
      'contactType' => $this->getSubmittedValue('contactType'),
      'contactSubType' => $this->getSubmittedValue('subType'),
      'dateFormats' => $this->getSubmittedValue('dateFormats'),
      'savedMapping' => $this->getSubmittedValue('savedMapping'),
    ];

    foreach ($storeParams as $storeName => $value) {
      $this->set($storeName, $value);
    }
    $this->set('disableUSPS', $this->getSubmittedValue('disableUSPS'));
    $this->set('dataSource', $this->getSubmittedValue('dataSource'));
    $this->set('skipColumnHeader', CRM_Utils_Array::value('skipColumnHeader', $this->_params));

    CRM_Core_Session::singleton()->set('dateTypes', $storeParams['dateFormats']);

    $this->instantiateDataSource();

    $mapper = [];

    $parser = new CRM_Contact_Import_Parser_Contact($mapper);
    $parser->setMaxLinesToProcess(100);
    $parser->setUserJobID($this->getUserJobID());
    $parser->run(NULL,
      [],
      CRM_Import_Parser::MODE_MAPFIELD,
      $this->getSubmittedValue('contactType'),
      '_id',
      '_status',
      CRM_Import_Parser::DUPLICATE_SKIP,
      NULL, NULL, FALSE,
      CRM_Contact_Import_Parser_Contact::DEFAULT_TIMEOUT,
      $this->getSubmittedValue('contactSubType'),
      $this->getSubmittedValue('dedupe_rule_id')
    );

    // add all the necessary variables to the form
    $parser->set($this);

  }

  /**
   * Instantiate the datasource.
   *
   * This gives the datasource a chance to do any table creation etc.
   *
   * @throws \CRM_Core_Exception
   */
  private function instantiateDataSource(): void {
    $dataSource = $this->getDataSourceObject();
    // Get the PEAR::DB object
    $dao = new CRM_Core_DAO();
    $db = $dao->getDatabaseConnection();
    $dataSource->postProcess($this->_params, $db, $this);
  }

  /**
   * General function for handling invalid configuration.
   *
   * I was going to statusBounce them all but when I tested I was 'bouncing' to weird places
   * whereas throwing an exception gave no behaviour change. So, I decided to centralise
   * and we can 'flip the switch' later.
   *
   * @param $message
   *
   * @throws \CRM_Core_Exception
   */
  protected function invalidConfig($message) {
    throw new CRM_Core_Exception($message);
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle(): string {
    return ts('Choose Data Source');
  }

}
