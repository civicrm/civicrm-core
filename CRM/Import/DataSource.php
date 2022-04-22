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

/**
 * This class defines the DataSource interface but must be subclassed to be
 * useful.
 */
abstract class CRM_Import_DataSource {

  /**
   * Class constructor.
   *
   * @param int|null $userJobID
   */
  public function __construct(int $userJobID = NULL) {
    if ($userJobID) {
      $this->setUserJobID($userJobID);
    }
  }

  /**
   * Form fields declared for this datasource.
   *
   * @var string[]
   */
  protected $submittableFields = [];

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
    return $this->userJobID;
  }

  /**
   * Set user job ID.
   *
   * @param int $userJobID
   */
  public function setUserJobID(int $userJobID): void {
    $this->userJobID = $userJobID;
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
   * Get submitted value.
   *
   * Get a value submitted on the form.
   *
   * @return mixed
   *
   * @throws \API_Exception
   */
  protected function getSubmittedValue(string $valueName) {
    return $this->getUserJob()['metadata']['submitted_values'][$valueName];
  }

  /**
   * Get rows as an array.
   *
   * The array has all values.
   *
   * @param int $limit
   * @param int $offset
   *
   * @return array
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function getRows(int $limit = 0, int $offset = 0) {
    $query = 'SELECT * FROM ' . $this->getTableName();
    if ($limit) {
      $query .= ' LIMIT ' . $limit . ($offset ? (' OFFSET ' . $offset) : NULL);
    }
    $rows = [];
    $result = CRM_Core_DAO::executeQuery($query);
    while ($result->fetch()) {
      $values = $result->toArray();
      /* trim whitespace around the values */
      foreach ($values as $k => $v) {
        $values[$k] = trim($v, " \t\r\n");
      }
      // Historically we expect a non-associative array...
      $rows[] = array_values($values);
    }
    return $rows;
  }

  /**
   * Get an array of column headers, if any.
   *
   * Null is returned when there are none - ie because a csv file does not
   * have an initial header row.
   *
   * This is presented to the user in the MapField screen so
   * that can see what fields they are mapping.
   *
   * @return array
   * @throws \API_Exception
   */
  public function getColumnHeaders(): array {
    return $this->getUserJob()['metadata']['DataSource']['column_headers'];
  }

  /**
   * Get an array of column headers, if any.
   *
   * Null is returned when there are none - ie because a csv file does not
   * have an initial header row.
   *
   * This is presented to the user in the MapField screen so
   * that can see what fields they are mapping.
   *
   * @return int
   * @throws \API_Exception
   */
  public function getNumberOfColumns(): int {
    return $this->getUserJob()['metadata']['DataSource']['number_of_columns'];
  }

  /**
   * Generated metadata relating to the the datasource.
   *
   * This is values that are computed within the DataSource class and
   * which are stored in the userJob metadata in the DataSource key - eg.
   *
   * ['table_name' => $]
   *
   * Will be in the user_job.metadata field encoded into the json like
   *
   * `{'DataSource' : ['table_name' => $], 'submitted_values' : .....}`
   *
   * @var array
   */
  protected $dataSourceMetadata = [];

  /**
   * Get metadata about the datasource.
   *
   * @return array
   *
   * @throws \API_Exception
   */
  public function getDataSourceMetadata(): array {
    if (!$this->dataSourceMetadata && $this->getUserJobID()) {
      $this->dataSourceMetadata = $this->getUserJob()['metadata']['DataSource'];
    }

    return $this->dataSourceMetadata;
  }

  /**
   * Get the table name for the datajob.
   *
   * @return string|null
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  protected function getTableName(): ?string {
    // The old name is still stored...
    $tableName = $this->getDataSourceMetadata()['table_name'];
    if (!$tableName) {
      return NULL;
    }
    if (strpos($tableName, 'civicrm_tmp_') !== 0
      || !CRM_Utils_Rule::alphanumeric($tableName)) {
      // The table name is generated and stored by code, not users so it
      // should be safe - but a check seems prudent all the same.
      throw new CRM_Core_Exception('Table cannot be deleted');
    }
    return $tableName;
  }

  /**
   * Get the fields declared for this datasource.
   *
   * @return string[]
   */
  public function getSubmittableFields(): array {
    return $this->submittableFields;
  }

  /**
   * Provides information about the data source.
   *
   * @return array
   *   Description of this data source, including:
   *   - title: string, translated, required
   *   - permissions: array, optional
   *
   */
  abstract public function getInfo();

  /**
   * This is function is called by the form object to get the DataSource's form snippet.
   *
   * It should add all fields necessary to get the data uploaded to the temporary table in the DB.
   *
   * @param CRM_Core_Form $form
   */
  abstract public function buildQuickForm(&$form);

  /**
   * Determine if the current user has access to this data source.
   *
   * @return bool
   */
  public function checkPermission() {
    $info = $this->getInfo();
    return empty($info['permissions']) || CRM_Core_Permission::check($info['permissions']);
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

}
