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

use Civi\Api4\UserJob;
use League\Csv\Writer;

/**
 * This class helps the forms within the import flow access submitted & parsed values.
 */
class CRM_Import_Forms extends CRM_Core_Form {

  /**
   * User job id.
   *
   * This is the primary key of the civicrm_user_job table which is used to
   * track the import.
   *
   * @var int
   */
  protected $userJobID;

  /**
   * @return int|null
   */
  public function getUserJobID(): ?int {
    if (!$this->userJobID && $this->get('user_job_id')) {
      $this->userJobID = $this->get('user_job_id');
    }
    return $this->userJobID;
  }

  /**
   * Set user job ID.
   *
   * @param int $userJobID
   */
  public function setUserJobID(int $userJobID): void {
    $this->userJobID = $userJobID;
    // This set allows other forms in the flow ot use $this->get('user_job_id').
    $this->set('user_job_id', $userJobID);
  }

  /**
   * User job details.
   *
   * This is the relevant row from civicrm_user_job.
   *
   * @var array
   */
  protected $userJob;

  /**
   * @var \CRM_Import_Parser
   */
  protected $parser;

  /**
   * Is the code being accessed in QuickForm mode.
   *
   * If false, ie functions being called to support the angular form, then we
   * 'quick-form-ify' the fields with dots over to double underscores.
   *
   * @var bool
   */
  protected $isQuickFormMode = TRUE;

  /**
   * Get User Job.
   *
   * API call to retrieve the userJob row.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getUserJob(): array {
    if (!$this->userJob) {
      $this->userJob = UserJob::get()
        ->addWhere('id', '=', $this->getUserJobID())
        ->execute()
        ->first();
    }
    return $this->userJob;
  }

  /**
   * Get submitted values stored in the user job.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getUserJobSubmittedValues(): array {
    return $this->getUserJob()['metadata']['submitted_values'];
  }

  /**
   * Fields that may be submitted on any form in the flow.
   *
   * @var string[]
   */
  protected $submittableFields = [
    // Skip column header is actually a field that would be added from the
    // datasource - but currently only in contact, it is always there for
    // other imports, ditto uploadFile.
    'skipColumnHeader' => 'DataSource',
    'fieldSeparator' => 'DataSource',
    'uploadFile' => 'DataSource',
    'contactType' => 'DataSource',
    'contactSubType' => 'DataSource',
    'dateFormats' => 'DataSource',
    'savedMapping' => 'DataSource',
    'dataSource' => 'DataSource',
    'dedupe_rule_id' => 'DataSource',
    'onDuplicate' => 'DataSource',
    'disableUSPS' => 'DataSource',
    'doGeocodeAddress' => 'DataSource',
    'multipleCustomData' => 'DataSource',
    // Note we don't add the save mapping instructions for MapField here
    // (eg 'updateMapping') - as they really are an action for that form
    // rather than part of the mapping config.
    'mapper' => 'MapField',
  ];

  /**
   * Get the submitted value, accessing it from whatever form in the flow it is
   * submitted on.
   *
   * @param string $fieldName
   *
   * @return mixed|null
   */
  public function getSubmittedValue(string $fieldName) {
    if ($fieldName === 'dataSource') {
      // Hard-coded handling for DataSource as it affects the contents of
      // getSubmittableFields and can cause a loop.
      // Note that the non-contact imports are not currently sharing the DataSource.tpl
      // that adds the CSV/SQL options & hence fall back on this hidden field.
      // - todo - switch to the same DataSource.tpl for all.
      return $this->controller->exportValue('DataSource', 'dataSource') ?? $this->controller->exportValue('DataSource', 'hidden_dataSource');
    }
    $mappedValues = $this->getSubmittableFields();
    if (array_key_exists($fieldName, $mappedValues)) {
      return $this->controller->exportValue($mappedValues[$fieldName], $fieldName);
    }
    return parent::getSubmittedValue($fieldName);

  }

  /**
   * Get values submitted on any form in the multi-page import flow.
   *
   * @return array
   */
  public function getSubmittedValues(): array {
    $values = [];
    foreach (array_keys($this->getSubmittableFields()) as $key) {
      $values[$key] = $this->getSubmittedValue($key);
    }
    return $values;
  }

  /**
   * Get the available datasource.
   *
   * Permission dependent, this will look like
   * [
   *   'CRM_Import_DataSource_CSV' => 'Comma-Separated Values (CSV)',
   *   'CRM_Import_DataSource_SQL' => 'SQL Query',
   * ]
   *
   * The label is translated.
   *
   * @return array
   */
  protected function getDataSources(): array {
    $dataSources = [];
    foreach (['CRM_Import_DataSource_SQL', 'CRM_Import_DataSource_CSV'] as $dataSourceClass) {
      $object = new $dataSourceClass();
      if ($object->checkPermission()) {
        $dataSources[$dataSourceClass] = $object->getInfo()['title'];
      }
    }
    return $dataSources;
  }

  /**
   * Get the name of the datasource class.
   *
   * This function prioritises retrieving from GET and POST over 'submitted'.
   * The reason for this is the submitted array will hold the previous submissions
   * data until after buildForm is called.
   *
   * This is problematic in the forward->back flow & option changing flow. As in....
   *
   * 1) Load DataSource form - initial default datasource is set to CSV and the
   * form is via ajax (this calls DataSourceConfig to get the data).
   * 2) User changes the source to SQL - the ajax updates the html but the
   * form was built with the expectation that the csv-specific fields would be
   * required.
   * 3) When the user submits Quickform calls preProcess and buildForm and THEN
   * retrieves the submitted values based on what has been added in buildForm.
   * Only the submitted values for fields added in buildForm are available - but
   * these have to be added BEFORE the submitted values are determined. Hence
   * we look in the POST or GET to get the updated value.
   *
   * Note that an imminent refactor will involve storing the values in the
   * civicrm_user_job table - this will hopefully help with a known (not new)
   * issue whereby the previously submitted values (eg. skipColumnHeader has
   * been checked or sql has been filled in) are not loaded via the ajax request.
   *
   * @return string|null
   *
   * @throws \CRM_Core_Exception
   */
  protected function getDataSourceClassName(): string {
    $className = CRM_Utils_Request::retrieveValue(
      'dataSource',
      'String'
    );
    if (!$className) {
      $className = $this->getSubmittedValue('dataSource');
    }
    if (!$className) {
      $className = $this->getDefaultDataSource();
    }
    if ($this->getDataSources()[$className]) {
      return $className;
    }
    throw new CRM_Core_Exception('Invalid data source');
  }

  /**
   * Allow the datasource class to add fields.
   *
   * This is called as a snippet in DataSourceConfig and
   * also from DataSource::buildForm to add the fields such
   * that quick form picks them up.
   *
   * @throws \CRM_Core_Exception
   */
  protected function buildDataSourceFields(): void {
    $dataSourceClass = $this->getDataSourceObject();
    if ($dataSourceClass) {
      $dataSourceClass->buildQuickForm($this);
    }
  }

  /**
   * Flush datasource on re-submission of the form.
   *
   * If the form has been re-submitted the datasource might have changed.
   * We tell the dataSource class to remove any tables (and potentially files)
   * created last form submission.
   *
   * If the DataSource in use is unchanged (ie still CSV or still SQL)
   * we also pass in the new variables. In theory it could decide that they
   * have not actually changed and it doesn't need to do any cleanup.
   *
   * In practice the datasource classes blast away as they always have for now
   * - however, the sql class, for example, might realise the fields it cares
   * about are unchanged and not flush the table.
   *
   * @throws \CRM_Core_Exception
   */
  protected function flushDataSource(): void {
    // If the form has been resubmitted the datasource might have changed.
    // We give the datasource a chance to clean up any tables it might have
    // created. If we are still using the same type of datasource (e.g still
    // an sql query
    $oldDataSource = $this->getUserJobSubmittedValues()['dataSource'];
    $oldDataSourceObject = new $oldDataSource($this->getUserJobID());
    $newParams = $this->getSubmittedValue('dataSource') === $oldDataSource ? $this->getSubmittedValues() : [];
    $oldDataSourceObject->purge($newParams);
  }

  /**
   * Get the relevant datasource object.
   *
   * @return \CRM_Import_DataSource|null
   *
   * @throws \CRM_Core_Exception
   */
  protected function getDataSourceObject(): ?CRM_Import_DataSource {
    $className = $this->getDataSourceClassName();
    if ($className) {
      return new $className($this->getUserJobID());
    }
    return NULL;
  }

  /**
   * Allow the datasource class to add fields.
   *
   * This is called as a snippet in DataSourceConfig and
   * also from DataSource::buildForm to add the fields such
   * that quick form picks them up.
   */
  protected function getDataSourceFields(): array {
    $className = $this->getDataSourceClassName();
    if ($className) {
      /** @var CRM_Import_DataSource $dataSourceClass */
      $dataSourceClass = new $className();
      return $dataSourceClass->getSubmittableFields();
    }
    return [];
  }

  /**
   * Get the default datasource.
   *
   * @return string
   */
  protected function getDefaultDataSource(): string {
    return 'CRM_Import_DataSource_CSV';
  }

  /**
   * Get the fields that can be submitted in the Import form flow.
   *
   * These could be on any form in the flow & are accessed the same way from
   * all forms.
   *
   * @return string[]
   */
  protected function getSubmittableFields(): array {
    $dataSourceFields = array_fill_keys($this->getDataSourceFields(), 'DataSource');
    return array_merge($this->submittableFields, $dataSourceFields);
  }

  /**
   * Get the contact type selected for the import (on the datasource form).
   *
   * @return string
   *   e.g Individual, Organization, Household.
   *
   * @throws \CRM_Core_Exception
   */
  protected function getContactType(): string {
    return $this->getSubmittedValue('contactType') ?? $this->getUserJob()['metadata']['entity_configuration']['Contact']['contact_type'];
  }

  /**
   * Get the contact sub type selected for the import (on the datasource form).
   *
   * @return string|null
   *   e.g Staff.
   *
   * @throws \CRM_Core_Exception
   */
  protected function getContactSubType(): ?string {
    return $this->getSubmittedValue('contactSubType');
  }

  /**
   * Create a user job to track the import.
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  protected function createUserJob(): int {
    $id = UserJob::create(FALSE)
      ->setValues([
        'created_id' => CRM_Core_Session::getLoggedInContactID(),
        'job_type' => $this->getUserJobType(),
        'status_id:name' => 'draft',
        // This suggests the data could be cleaned up after this.
        'expires_date' => '+ 1 week',
        'metadata' => [
          'submitted_values' => $this->getSubmittedValues(),
        ],
      ])
      ->execute()
      ->first()['id'];
    $this->setUserJobID($id);
    return $id;
  }

  /**
   * @param string $key
   * @param array $data
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function updateUserJobMetadata(string $key, array $data): void {
    $metaData = array_merge(
      $this->getUserJob()['metadata'],
      [$key => $data]
    );
    UserJob::update(FALSE)
      ->addWhere('id', '=', $this->getUserJobID())
      ->setValues(['metadata' => $metaData])
      ->execute();
    $this->userJob['metadata'] = $metaData;
  }

  /**
   * Get column headers for the datasource or empty array if none apply.
   *
   * This would be the first row of a csv or the fields in an sql query.
   *
   * If the csv does not have a header row it will be empty.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getColumnHeaders(): array {
    return $this->getDataSourceObject()->getColumnHeaders();
  }

  /**
   * Get the number of importable columns in the data source.
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  protected function getNumberOfColumns(): int {
    return $this->getDataSourceObject()->getNumberOfColumns();
  }

  /**
   * Get x data rows from the datasource.
   *
   * At this stage we are fetching from what has been stored in the form
   * during `postProcess` on the DataSource form.
   *
   * In the future we will use the dataSource object, likely
   * supporting offset as well.
   *
   * @return array|int
   *   One or more of the statues available - e.g
   *   CRM_Import_Parser::VALID
   *   or [CRM_Import_Parser::ERROR, CRM_Import_Parser::VALID]
   *
   * @throws \CRM_Core_Exception
   */
  protected function getDataRows($statuses = [], int $limit = 0): array {
    $statuses = (array) $statuses;
    return $this->getDataSourceObject()->setLimit($limit)->setStatuses($statuses)->getRows();
  }

  /**
   * Get the datasource rows ready for csv output.
   *
   * @param array $statuses
   * @param int $limit
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getOutputRows($statuses = [], int $limit = 0) {
    $statuses = (array) $statuses;
    $dataSource = $this->getDataSourceObject()->setLimit($limit)->setStatuses($statuses)->setStatuses($statuses);
    $dataSource->setSelectFields(array_merge(['_id', '_status_message'], $dataSource->getDataFieldNames()));
    return $dataSource->getRows();
  }

  /**
   * Get the column headers for the output csv.
   *
   * @return array
   */
  protected function getOutputColumnsHeaders(): array {
    $headers = $this->getColumnHeaders();
    array_unshift($headers, ts('Reason'));
    array_unshift($headers, ts('Line Number'));
    return $headers;
  }

  /**
   * Get the number of rows with the specified status.
   *
   * @param array|int $statuses
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   */
  protected function getRowCount($statuses = []) {
    $statuses = (array) $statuses;
    return $this->getDataSourceObject()->getRowCount($statuses);
  }

  /**
   * Outputs and downloads the csv of outcomes from an import job.
   *
   * This gets the rows from the temp table that match the relevant status
   * and output them as a csv.
   *
   * @throws \CRM_Core_Exception
   * @throws \League\Csv\CannotInsertRecord
   * @throws \CRM_Core_Exception
   */
  public static function outputCSV(): void {
    $userJobID = CRM_Utils_Request::retrieveValue('user_job_id', 'Integer', NULL, TRUE);
    $status = (int) CRM_Utils_Request::retrieveValue('status', 'String', NULL, TRUE);
    $saveFileName = CRM_Import_Parser::saveFileName($status);

    $form = new CRM_Import_Forms();
    $form->controller = new CRM_Core_Controller();
    $form->set('user_job_id', $userJobID);

    $form->getUserJob();
    $writer = Writer::createFromFileObject(new SplTempFileObject());
    $headers = $form->getOutputColumnsHeaders();
    $writer->insertOne($headers);
    // Note this might be more inefficient by iterating the result
    // set & doing insertOne - possibly something to explore later.
    $writer->insertAll($form->getOutputRows($status));
    $writer->output($saveFileName);
    CRM_Utils_System::civiExit();
  }

  /**
   * Get the url to download the relevant csv file.
   * @param string $status
   *
   * @return string
   */
  protected function getDownloadURL(string $status): string {
    return CRM_Utils_System::url('civicrm/import/outcome', [
      'user_job_id' => $this->get('user_job_id'),
      'status' => $status,
      'reset' => 1,
    ]);
  }

  /**
   * Get the url to download the relevant csv file.
   * @param string $status
   *
   * @return string
   */

  /**
   *
   * @return array
   */
  public function getTrackingSummary(): array {
    $summary = [];
    $fields = $this->getParser()->getTrackingFields();
    $row = $this->getDataSourceObject()->setAggregateFields($fields)->getRow();
    foreach ($fields as $fieldName => $field) {
      $summary[] = [
        'field_name' => $fieldName,
        'description' => $field['description'],
        'value' => $row[$fieldName],
      ];
    }

    return $summary;
  }

  /**
   * Get the fields available for import selection.
   *
   * @return array
   *   e.g ['first_name' => 'First Name', 'last_name' => 'Last Name'....
   *
   * @throws \CRM_Core_Exception
   */
  protected function getAvailableFields(): array {
    $return = [];
    foreach ($this->getFields() as $name => $field) {
      if ($name === 'id' && $this->isSkipDuplicates()) {
        // Duplicates are being skipped so id matching is not available.
        continue;
      }
      if (($field['entity'] ?? '') === 'Contact' && $this->isFilterContactFields() && empty($field['match_rule'])) {
        // Filter out metadata that is intended for create & update - this is not available in the quick-form
        // but is now loaded in the Parser for the LexIM variant.
        continue;
      }
      // Swap out dots for double underscores so as not to break the quick form js.
      // We swap this back on postProcess.
      $name = str_replace('.', '__', $name);
      $return[$name] = $field['html']['label'] ?? $field['title'];
    }
    return $return;
  }

  /**
   * Should contact fields be filtered which determining fields to show.
   *
   * This applies to Contribution import as we put all contact fields in the metadata
   * but only present those used for a match - but will permit create via LeXIM.
   *
   * @return bool
   */
  protected function isFilterContactFields() : bool {
    return FALSE;
  }

  /**
   * Get the fields available for import selection.
   *
   * @return array
   *   e.g ['first_name' => 'First Name', 'last_name' => 'Last Name'....
   *
   */
  protected function getFields(): array {
    return $this->getParser()->getFieldsMetadata();
  }

  /**
   * Get the fields available for import selection.
   *
   * @return array
   *   e.g ['first_name' => 'First Name', 'last_name' => 'Last Name'....
   *
   */
  protected function getImportEntities(): array {
    return $this->getParser()->getImportEntities();
  }

  /**
   * Get an instance of the parser class.
   *
   * @return \CRM_Contact_Import_Parser_Contact|\CRM_Contribute_Import_Parser_Contribution
   */
  protected function getParser() {
    foreach (CRM_Core_BAO_UserJob::getTypes() as $jobType) {
      if ($jobType['id'] === $this->getUserJob()['job_type']) {
        $className = $jobType['class'];
        $classObject = new $className();
        $classObject->setUserJobID($this->getUserJobID());
        return $classObject;
      };
    }
    return NULL;
  }

  /**
   * Get the mapped fields as an array of labels.
   *
   * e.g
   * ['First Name', 'Employee Of - First Name', 'Home - Street Address']
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getMappedFieldLabels(): array {
    $mapper = [];
    $parser = $this->getParser();
    $importMappings = $this->getUserJob()['metadata']['import_mappings'] ?? [];
    if (empty($importMappings)) {
      foreach ($this->getSubmittedValue('mapper') as $columnNumber => $mapping) {
        $importMappings[] = $parser->getMappingFieldFromMapperInput((array) $mapping, 0, $columnNumber);
      }
    }
    foreach ($importMappings as $columnNumber => $importMapping) {
      $mapper[$columnNumber] = $parser->getMappedFieldLabel($importMapping);
    }
    return $mapper;
  }

  /**
   * Assign variables required for the MapField form.
   *
   * @throws \CRM_Core_Exception
   */
  protected function assignMapFieldVariables(): void {
    $this->addExpectedSmartyVariables(['highlightedRelFields', 'initHideBoxes']);
    $this->_columnCount = $this->getNumberOfColumns();
    $this->_columnNames = $this->getColumnHeaders();
    $this->_dataValues = array_values($this->getDataRows([], 2));
    $this->assign('columnNames', $this->getColumnHeaders());
    $this->assign('showColumnNames', $this->getSubmittedValue('skipColumnHeader') || $this->getSubmittedValue('dataSource') !== 'CRM_Import_DataSource');
    $this->assign('highlightedFields', $this->getHighlightedFields());
    $this->assign('columnCount', $this->_columnCount);
    $this->assign('dataValues', $this->_dataValues);
  }

  /**
   * Get the fields to be highlighted in the UI.
   *
   * The highlighted fields are those used to match
   * to an existing entity.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getHighlightedFields(): array {
    return [];
  }

  /**
   * Get the data patterns to pattern match the incoming data.
   *
   * @return array
   */
  public function getHeaderPatterns(): array {
    $headerPatterns = [];
    foreach ($this->getFields() as $name => $field) {
      if (empty($field['headerPattern']) || $field['headerPattern'] === '//') {
        continue;
      }
      // Swap out dots for double underscores so as not to break the quick form js.
      // We swap this back on postProcess.
      $name = str_replace('.', '__', $name);
      $headerPatterns[$name] = $field['headerPattern'];
    }
    return $headerPatterns;
  }

  /**
   * Has the user chosen to update existing records.
   * @return bool
   */
  protected function isUpdateExisting(): bool {
    return ((int) $this->getSubmittedValue('onDuplicate')) === CRM_Import_Parser::DUPLICATE_UPDATE;
  }

  /**
   * Has the user chosen to update existing records.
   * @return bool
   */
  protected function isSkipExisting(): bool {
    return ((int) $this->getSubmittedValue('onDuplicate')) === CRM_Import_Parser::DUPLICATE_SKIP;
  }

  /**
   * Did the user specify duplicates should be skipped and not imported.
   *
   * @return bool
   */
  protected function isSkipDuplicates(): bool {
    return ((int) $this->getSubmittedValue('onDuplicate')) === CRM_Import_Parser::DUPLICATE_SKIP;
  }

  /**
   * Are there valid rows to import.
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  protected function hasImportableRows(): bool {
    return (bool) $this->getRowCount(['new']);
  }

  /**
   * Get the base entity for the import.
   *
   * @return string
   */
  protected function getBaseEntity(): string {
    $info = $this->getParser()->getUserJobInfo();
    return reset($info)['entity'];
  }

  /**
   * Assign values for civiimport.
   *
   * I wanted to put this in the extension - but there are a lot of protected functions
   * we would need to revisit and make public - do we want to?
   *
   * @throws \CRM_Core_Exception
   */
  public function assignCiviimportVariables(): void {
    $contactTypes = [];
    foreach (CRM_Contact_BAO_ContactType::basicTypeInfo() as $contactType) {
      $contactTypes[] = ['id' => $contactType['name'], 'text' => $contactType['label']];
    }
    $parser = $this->getParser();
    $this->isQuickFormMode = FALSE;
    Civi::resources()->addVars('crmImportUi', [
      'defaults' => $this->getDefaults(),
      'rows' => $this->getDataRows([], 2),
      'contactTypes' => $contactTypes,
      'entityMetadata' => $this->getFieldOptions(),
      'dedupeRules' => $parser->getAllDedupeRules(),
      'userJob' => $this->getUserJob(),
    ]);
  }

}
