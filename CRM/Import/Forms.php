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
   * Get User Job.
   *
   * API call to retrieve the userJob row.
   *
   * @return array
   *
   * @throws \API_Exception
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
   * @throws \API_Exception
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
      return $this->controller->exportValue('DataSource', 'dataSource');
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
   * @throws \API_Exception
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
      /* @var CRM_Import_DataSource $dataSource */
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
      /* @var CRM_Import_DataSource $dataSourceClass */
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
    $contactTypeMapping = [
      CRM_Import_Parser::CONTACT_INDIVIDUAL => 'Individual',
      CRM_Import_Parser::CONTACT_HOUSEHOLD => 'Household',
      CRM_Import_Parser::CONTACT_ORGANIZATION => 'Organization',
    ];
    return $contactTypeMapping[$this->getSubmittedValue('contactType')];
  }

  /**
   * Create a user job to track the import.
   *
   * @return int
   *
   * @throws \API_Exception
   */
  protected function createUserJob(): int {
    $id = UserJob::create(FALSE)
      ->setValues([
        'created_id' => CRM_Core_Session::getLoggedInContactID(),
        'type_id:name' => 'contact_import',
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
   * @throws \API_Exception
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
   * @throws \API_Exception
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
   * @throws \API_Exception
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
   * @param int $limit
   * @param int $offset
   * @param int|array $statuses
   *   Optional status filter.
   *
   * @return array
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  protected function getDataRows(int $limit, $offset = 0, $statuses = []): array {
    $statuses = (array) $statuses;
    return $this->getDataSourceObject()->getRows($limit, $offset, $statuses);
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
   *   or [CRM_Import_Parser::ERROR, CRM_Import_Parser::CONFLICT]
   *
   * @throws \CRM_Core_Exception
   * @throws \API_Exception
   */
  protected function getRowCount($statuses = []): int {
    $statuses = (array) $statuses;
    return $this->getDataSourceObject()->getRowCount($statuses);
  }

  /**
   * Outputs and downloads the csv of outcomes from an import job.
   *
   * This gets the rows from the temp table that match the relevant status
   * and output them as a csv.
   *
   * @throws \API_Exception
   * @throws \League\Csv\CannotInsertRecord
   * @throws \CRM_Core_Exception
   */
  public static function outputCSV(): void {
    $userJobID = CRM_Utils_Request::retrieveValue('user_job_id', 'Integer', NULL, TRUE);
    $status = CRM_Utils_Request::retrieveValue('status', 'String', NULL, TRUE);
    $saveFileName = CRM_Import_Parser::saveFileName($status);

    $form = new CRM_Import_Forms();
    $form->controller = new CRM_Core_Controller();
    $form->set('user_job_id', $userJobID);

    $form->getUserJob();
    $writer = Writer::createFromFileObject(new SplTempFileObject());
    $headers = $form->getColumnHeaders();
    if ($headers) {
      array_unshift($headers, ts('Reason'));
      array_unshift($headers, ts('Line Number'));
      $writer->insertOne($headers);
    }
    $writer->addFormatter(['CRM_Import_Forms', 'reorderOutput']);
    // Note this might be more inefficient that iterating the result
    // set & doing insertOne - possibly something to explore later.
    $writer->insertAll($form->getDataRows(0, 0, $status));

    CRM_Utils_System::setHttpHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
    CRM_Utils_System::setHttpHeader('Content-Description', 'File Transfer');
    CRM_Utils_System::setHttpHeader('Content-Type', 'text/csv; charset=UTF-8');
    $writer->output($saveFileName);
    CRM_Utils_System::civiExit();
  }

  /**
   * When outputting the row as a csv, more the last 2 rows to the start.
   *
   * This is because the id and status message fields are at the end. It may make sense
   * to move them to the start later, when order code cleanup has happened...
   *
   * @param array $record
   */
  public static function reorderOutput(array $record): array {
    $rowNumber = array_pop($record);
    $message = array_pop($record);
    // Also pop off the status - but we are not going to use this at this stage.
    array_pop($record);
    array_unshift($record, $message);
    array_unshift($record, $rowNumber);
    return $record;
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
   * Get the fields available for import selection.
   *
   * @return array
   *   e.g ['first_name' => 'First Name', 'last_name' => 'Last Name'....
   *
   * @throws \API_Exception
   */
  protected function getAvailableFields(): array {
    $parser = new CRM_Contact_Import_Parser_Contact();
    $parser->setUserJobID($this->getUserJobID());
    return $parser->getAvailableFields();
  }

}
