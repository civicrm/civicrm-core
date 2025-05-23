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

namespace Civi\UserJob;

use Civi\Api4\UserJob;

trait UserJobTrait {

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
   * The user job in use.
   *
   * @var array
   */
  protected $userJob;

  /**
   * @return int|null
   */
  public function getUserJobID(): ?int {
    if (!$this->userJobID && is_a($this, 'CRM_Core_Form') && $this->get('user_job_id')) {
      $this->userJobID = $this->get('user_job_id');
    }
    return $this->userJobID;
  }

  /**
   * Set user job ID.
   *
   * @param int $userJobID
   *
   * @return self
   */
  public function setUserJobID(int $userJobID): self {
    $this->userJobID = $userJobID;
    // This allows other forms in the flow ot use $this->get('user_job_id').
    if (is_a($this, 'CRM_Core_Form')) {
      $this->set('user_job_id', $userJobID);
    }
    return $this;
  }

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
    if (empty($this->userJob)) {
      $this->userJob = UserJob::get()
        ->addSelect('*', 'search_display_id.name', 'search_display_id.saved_search_id.name')
        ->addWhere('id', '=', $this->getUserJobID())
        ->execute()
        ->single();
    }
    return $this->userJob;
  }

}
