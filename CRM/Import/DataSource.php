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
   * @return array
   */
  public function getDataSourceMetadata(): array {
    return $this->dataSourceMetadata;
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
   * Set variables up before form is built.
   *
   * @param CRM_Core_Form $form
   */
  abstract public function preProcess(&$form);

  /**
   * This is function is called by the form object to get the DataSource's form snippet.
   *
   * It should add all fields necessary to get the data uploaded to the temporary table in the DB.
   *
   * @param CRM_Core_Form $form
   */
  abstract public function buildQuickForm(&$form);

  /**
   * Process the form submission.
   *
   * @param array $params
   * @param string $db
   * @param CRM_Core_Form $form
   */
  abstract public function postProcess(&$params, &$db, &$form);

  /**
   * Determine if the current user has access to this data source.
   *
   * @return bool
   */
  public function checkPermission() {
    $info = $this->getInfo();
    return empty($info['permissions']) || CRM_Core_Permission::check($info['permissions']);
  }

}
