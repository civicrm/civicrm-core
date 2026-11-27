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

  protected function getSubmissionId() {
    return $this->submissionId;
  }

  /**
   * The stored values passed this check when they were submitted.
   */
  protected function assertKnownFileId($fileId, string $entityName, string $apiEntity, $recordId, string $fieldName, $formDefault = NULL, ?string $joinEntity = NULL): void {
  }

  protected function processForm() {
    // get the submitted data
    $afformSubmissionData = AfformSubmission::get(FALSE)
      ->addSelect('data')
      ->addWhere('id', '=', $this->submissionId)
      ->addWhere('afform_name', '=', $this->name)
      ->addWhere('status_id:name', '=', 'Pending')
      ->execute()->first();

    // return early if nothing to process
    if (empty($afformSubmissionData)) {
      return [];
    }

    // preprocess submitted values
    $this->_entityValues = $this->preprocessSubmittedValues($afformSubmissionData['data']);

    \CRM_Core_Transaction::create()->run(function () use ($afformSubmissionData) {
      // process and save various enities
      $this->processFormData($this->_entityValues);

      // combine ids with existing data
      $submissionData = $this->combineValuesAndIds($afformSubmissionData['data'], $this->_entityIds);

      // Update submission record with entity IDs.
      AfformSubmission::update(FALSE)
        ->addWhere('id', '=', $this->submissionId)
        ->addValue('data', $submissionData)
        ->addValue('status_id:name', 'Processed')
        ->execute();
    });
    $this->dispatchSubmitted();

    foreach ($this->_entityIds as $key => $value) {
      $this->setResponseItem($key, $value);
    }

    if (isset($this->_response['redirect']) || isset($this->_response['message'])) {
      // redirect / message is already set, ignore defaults
    }
    elseif (($this->_afform['verification_confirmation_type'] ?? NULL) === 'show_confirmation_message') {
      if (!empty($this->_afform['verification_confirmation_message'])) {
        $message = $this->replaceTokens($this->_afform['verification_confirmation_message']);
        $this->setResponseItem('message', $message);
      }
    }
    elseif (!empty($this->_afform['verification_redirect'])) {
      $redirect = $this->replaceTokens($this->_afform['verification_redirect'], 'url');
      $this->setResponseItem('redirect', $redirect);
    }

    return [$this->_response];
  }

}
