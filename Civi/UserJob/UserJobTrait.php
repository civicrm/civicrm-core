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

use Civi\Api4\ImportTemplateField;
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
        ->addWhere('id', '=', $this->getUserJobID())
        ->execute()
        ->single();
      $this->userJob['template_fields'] = (array) ImportTemplateField::get(FALSE)
        ->addWhere('user_job_id', '=', $this->getUserJobID())
        ->addOrderBy('column_number')
        ->execute();
      // Compat: template_fields was stored in the metadata array circa v6.1.
      if (!$this->userJob['template_fields'] && !empty($this->userJob['metadata']['import_mappings'])) {
        $this->userJob['template_fields'] = $this->convertTemplateFieldFormat($this->userJob['metadata']['import_mappings']);
      }
    }
    return $this->userJob;
  }

  /**
   * Convert import mappings to new template fields format.
   *
   * Temporary compat function, because
   * template_fields was stored in the metadata array circa v6.1.
   *
   * @param array $importMappings
   *   An array of import mappings where each mapping contains 'name',
   *   'entity_data', and 'column_number'.
   *
   * @return array
   */
  public function convertTemplateFieldFormat(array $importMappings): array {
    $templateFields = [];
    $baseEntity = $this->getBaseEntity();
    $prefixMap = [
      'target_contact.' => 'TargetContact',
      'source_contact.' => 'SourceContact',
      'contact.' => 'Contact',
      'soft_credit.contact.' => 'SoftCreditContact',
    ];
    foreach ($importMappings as $importMapping) {
      if (empty($importMapping['name'])) {
        continue;
      }
      $entity = $baseEntity;
      $name = $importMapping['name'];
      foreach ($prefixMap as $prefix => $prefixEntity) {
        if (str_starts_with($name, $prefix)) {
          $entity = $prefixEntity;
          $name = substr($name, strlen($prefix));
        }
      }
      $templateFields[] = [
        'name' => $name,
        'entity' => $entity,
        'data' => $importMapping['entity_data'] ?? NULL,
        'column_number' => $importMapping['column_number'] + 1,
      ];
    }
    return $templateFields;
  }

}
