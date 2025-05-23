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

use Civi\Api4\Mapping;
use Civi\Api4\UserJob;
use Civi\Core\ClassScanner;
use Civi\Import\DataSource\DataSourceInterface;
use League\Csv\Writer;

/**
 * This class helps the forms within the import flow access submitted & parsed values.
 */
class CRM_Import_Forms extends CRM_Core_Form {
  use \Civi\UserJob\UserJobTrait;

  /**
   * @var int
   */
  protected $templateID;

  /**
   * Name of the import mapping (civicrm_mapping).
   *
   * @var string
   */
  protected $mappingName;

  /**
   * The id of the saved mapping being updated.
   *
   * Note this may not be the same as the saved mapping being used to
   * load data. Use the `getSavedMappingID` function to access & any
   * extra logic can be added in there.
   *
   * @var int
   */
  protected $savedMappingID;

  /**
   * @param int $savedMappingID
   *
   * @return CRM_Import_Forms
   */
  public function setSavedMappingID(int $savedMappingID): CRM_Import_Forms {
    $this->savedMappingID = $savedMappingID;
    return $this;
  }

  /**
   * Get the name of the type to be stored in civicrm_user_job.type_id.
   *
   * This should be overridden.
   *
   * @return string
   */
  public function getUserJobType(): string {
    CRM_Core_Error::deprecatedWarning('this function should be overridden');
    return '';
  }

  public function getEntity() {
    return $this->controller->getEntity();
  }

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
    'contactType' => 'DataSource',
    'contactSubType' => 'DataSource',
    'dateFormats' => 'DataSource',
    'savedMapping' => 'DataSource',
    'dataSource' => 'DataSource',
    'use_existing_upload' => 'DataSource',
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
   * @throws \CRM_Core_Exception
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
   * Get the template ID from the url, if available.
   *
   * Otherwise there are other possibilities...
   *  - it could already be saved to our UserJob.
   *  - on the DataSource form we could determine if from the savedMapping field
   *  (which will hold an ID that can be used to load it). We want to check this is
   *  coming from the POST (ie fresh)
   *  - on the MapField form it could be derived from the new mapping created from
   *   saveMapping + saveMappingName.
   *
   * @return int|null
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function getTemplateID(): ?int {
    if ($this->templateID === NULL) {
      $this->templateID = CRM_Utils_Request::retrieve('template_id', 'Int', $this);
      if ($this->templateID && $this->getTemplateJob()) {
        return $this->templateID;
      }
      if ($this->getUserJobID()) {
        $this->templateID = $this->getUserJob()['metadata']['template_id'] ?? NULL;
      }
      elseif (!empty($this->getSubmittedValue('savedMapping'))) {
        if (!$this->getTemplateJob()) {
          $this->createTemplateJob();
        }
      }
    }
    return $this->templateID ?? NULL;
  }

  /**
   * @return string
   *
   * @throws \CRM_Core_Exception
   */
  protected function getMappingName(): string {
    if ($this->mappingName === NULL) {
      $savedMappingID = $this->getSavedMappingID();
      if ($savedMappingID) {
        $this->mappingName = Mapping::get(FALSE)
          ->addWhere('id', '=', $savedMappingID)
          ->execute()
          ->first()['name'];
      }
    }
    return $this->mappingName ?? '';
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
    $classes = ClassScanner::get(['interface' => DataSourceInterface::class]);
    foreach ($classes as $dataSourceClass) {
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
   * 3) When the user submits QuickForm calls preProcess and buildForm and THEN
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
    $oldDataSource = $this->getUserJobSubmittedValues()['dataSource'] ?? NULL;
    if ($oldDataSource) {
      // Absence of an old data source likely means a template has been used (hence
      // the user job exists) - but templates don't have data sources - so nothing to flush.
      $oldDataSourceObject = new $oldDataSource($this->getUserJobID());
      $newParams = $this->getSubmittedValue('dataSource') === $oldDataSource ? $this->getSubmittedValues() : [];
      $oldDataSourceObject->purge($newParams);
    }
    $this->updateUserJobMetadata('DataSource', []);
  }

  /**
   * Is the data already uploaded.
   *
   * This would be true on the DataSource screen when using the back button
   * and ideally we can re-use that data rather than make them upload anew.
   *
   * @throws \CRM_Core_Exception
   */
  protected function isImportDataUploaded(): bool {
    return $this->getUserJobID() && !empty($this->getUserJob()['metadata']['DataSource']['table_name']);
  }

  /**
   * Get the relevant datasource object.
   *
   * @return \Civi\Import\DataSource\DataSourceInterface|null
   * @throws \CRM_Core_Exception
   */
  protected function getDataSourceObject(): ?DataSourceInterface {
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
   *
   * @throws \CRM_Core_Exception
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
   * @throws \CRM_Core_Exception
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
          'template_id' => $this->getTemplateID(),
          'Template' => ['mapping_id' => $this->getSavedMappingID()],
        ],
      ])
      ->execute()
      ->first()['id'];
    $this->setUserJobID($id);
    return $id;
  }

  protected function createTemplateJob(): void {
    if (!$this->getUserJobType()) {
      // This could be hit in extensions while they transition.
      CRM_Core_Error::deprecatedWarning('Classes should implement getUserJobType');
      return;
    }
    $this->templateID = UserJob::create(FALSE)->setValues([
      'is_template' => 1,
      'created_id' => CRM_Core_Session::getLoggedInContactID(),
      'job_type' => $this->getUserJobType(),
      'status_id:name' => 'draft',
      'name' => 'import_' . $this->getMappingName(),
      'metadata' => ['submitted_values' => $this->getSubmittedValues()],
    ])->execute()->first()['id'];
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
    $this->getUserJob()['metadata'] = $metaData;
    if ($this->isUpdateTemplateJob()) {
      $this->updateTemplateUserJob($metaData);
    }
    // We likely don't need the empty check. A precaution against nulling it out by accident.
    if (empty($metaData['template_id'])) {
      $metaData['template_id'] = $this->templateID;
    }
    UserJob::update(FALSE)
      ->addWhere('id', '=', $this->getUserJobID())
      ->setValues(['metadata' => $metaData])
      ->execute();
    $this->userJob['metadata'] = $metaData;
  }

  /**
   * Is the user wanting to update the template / mapping.
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  protected function isUpdateTemplateJob(): bool {
    return $this->getSubmittedValue('updateMapping') || $this->getSubmittedValue('saveMapping');
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
    $headers = $this->getDataSourceObject()->getColumnHeaders();
    $mappedFields = $this->getUserJob()['metadata']['import_mappings'] ?? [];
    if (!empty($mappedFields) && count($mappedFields) > count($headers)) {
      // The user has mapped one or more non-database fields, add those in.
      $userMappedFields = array_diff_key($mappedFields, $headers);
      foreach ($userMappedFields as $field) {
        $headers[] = '';
      }
    }
    return $headers;
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
    $rows = $this->getDataSourceObject()->setLimit($limit)->setStatuses($statuses)->getRows();
    $headers = $this->getColumnHeaders();
    $mappings = $this->getUserJob()['metadata']['import_mappings'] ?? [];
    foreach ($rows as &$row) {
      foreach ($headers as $index => $header) {
        if (!$header) {
          // Our rows are sequential lists of the values in the database table but the database
          // table has some non-mapping related rows (`_status`, `_statusMessage` etc)
          // and our mappings have some virtual rows, which do not have headers
          // so, we populate our virtual values here.
          $row[$index] = $mappings[$index]['default_value'] ?? '';
        }
      }
    }
    return $rows;
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
   * Function is accessed from civicrm/import/outcome path.
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
   * Get information about the user job parser.
   *
   * This is as per `CRM_Core_BAO_UserJob::getTypes()`
   *
   * @return array
   */
  protected function getUserJobInfo(): array {
    $importInformation = $this->getParser()->getUserJobInfo();
    return reset($importInformation);
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
      if (($field['entity'] ?? '') === 'Contact' && $this->isFilterContactFields() && empty($field['match_rule'])) {
        // Filter out metadata that is intended for create & update - this is not available in the quick-form
        // but is now loaded in the Parser for the LexIM variant.
        continue;
      }
      $return[$name] = $field['title'];
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
   * @throws \CRM_Core_Exception
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
      if (!empty($field['usage']['import']) && !empty($field['title'])) {
        $patterns = [
          $this->strToPattern($field['name']),
          $this->strToPattern($field['title']),
        ];
        if (!empty($field['html']['label'])) {
          $patterns[] = $this->strToPattern($field['html']['label']);
        }
        // Swap out dots for double underscores so as not to break the quick form js.
        // We swap this back on postProcess.
        $name = str_replace('.', '__', $name);
        $headerPatterns[$name] = '/^' . implode('|', array_unique($patterns)) . '$/i';
      }
    }
    return $headerPatterns;
  }

  private function strToPattern(string $str) {
    $str = str_replace(['_', '-'], ' ', $str);
    return strtolower(str_replace(' ', '[-_ ]?', preg_quote($str, '/')));
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
   *
   * @throws \CRM_Core_Exception
   */
  protected function getBaseEntity(): string {
    if ($this->getUserJobID()) {
      $info = $this->getParser()->getUserJobInfo();
      return reset($info)['entity'];
    }
    return CRM_Core_BAO_UserJob::getTypeValue($this->getUserJobType(), 'entity');
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
      'columnHeaders' => $this->getColumnHeaders(),
    ]);
  }

  /**
   * Get the UserJob Template, if it exists.
   *
   * @return array|null
   *
   * @throws \CRM_Core_Exception
   */
  protected function getTemplateJob(): ?array {
    $mappingName = $this->getMappingName();
    if (!$mappingName) {
      return NULL;
    }
    $templateJob = UserJob::get(FALSE)
      ->addWhere('name', '=', 'import_' . $mappingName)
      ->addWhere('is_template', '=', TRUE)
      ->execute()->first();
    $this->templateID = $templateJob['id'] ?? NULL;
    return $templateJob ?? NULL;
  }

  /**
   * @param array $metaData
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function updateTemplateUserJob(array $metaData): void {
    if ($this->getTemplateID()) {
      UserJob::update(FALSE)
        ->addWhere('id', '=', $this->getTemplateID())
        ->setValues(['metadata' => $metaData, 'is_template' => TRUE])
        ->execute();
    }
    elseif ($this->getMappingName()) {
      $this->createTemplateJob();
    }
  }

  /**
   * Get the saved mapping ID being updated.
   *
   * @return int|null
   */
  public function getSavedMappingID(): ?int {
    if (!$this->savedMappingID) {
      if (!empty($this->getUserJob()['metadata']['Template']['mapping_id'])) {
        $this->savedMappingID = $this->getUserJob()['metadata']['Template']['mapping_id'];
      }
    }
    return $this->savedMappingID;
  }

}
