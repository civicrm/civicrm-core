<?php

namespace Civi\Api4\Action\Afform;

use Civi\Api4\AfformSubmission;

/**
 * Class Process
 * @package Civi/Api4/Action/Afform
 */
class Process extends AbstractProcessor {

  /**
   * Submission id
   * @var int
   * @required
   */
  protected $submissionId;

  protected function processForm() {
    // get the submitted data
    $afformSubmissionData = AfformSubmission::get(FALSE)
      ->addSelect('data')
      ->addWhere('id', '=', $this->submissionId)
      ->addWhere('status_id:name', '=', 'Pending')
      ->execute()->first();

    // return early if nothing to process
    if (empty($afformSubmissionData)) {
      return [];
    }

    // preprocess submitted values
    $entityValues = $this->preprocessSubmittedValues($afformSubmissionData['data']);

    // process and save various enities
    $this->processFormData($entityValues);

    // combine ids with existing data
    $submissionData = $this->combineValuesAndIds($afformSubmissionData['data'], $this->_entityIds);

    // Update submission record with entity IDs.
    AfformSubmission::update(FALSE)
      ->addWhere('id', '=', $this->submissionId)
      ->addValue('data', $submissionData)
      ->addValue('status_id:name', 'Processed')
      ->execute();

    return $this->_entityIds;
  }

}
