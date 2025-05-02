<?php

namespace Civi\Api4\Action\Afform;

/**
 * Class Prefill
 *
 * @package Civi\Api4\Action\Afform
 */
class Prefill extends AbstractProcessor {

  protected function processForm() {
    $entityValues = $this->_entityValues;
    return \CRM_Utils_Array::makeNonAssociative($entityValues, 'name', 'values');
  }

  protected function loadEntities() {
    if ($this->fillMode === 'form') {
      if (!empty($this->args['sid'])) {
        $afformSubmission = \Civi\Api4\AfformSubmission::get()
          ->addSelect('data')
          ->addWhere('id', '=', $this->args['sid'])
          ->addWhere('afform_name', '=', $this->name)
          ->execute()->first();
      }
      // Restore saved draft
      elseif (\CRM_Core_Session::getLoggedInContactID()) {
        $afformSubmission = \Civi\Api4\AfformSubmission::get(FALSE)
          ->addSelect('data')
          ->addWhere('contact_id', '=', \CRM_Core_Session::getLoggedInContactID())
          ->addWhere('afform_name', '=', $this->name)
          ->addWhere('status_id:name', '=', 'Draft')
          ->execute()->first();
      }
    }
    if (!empty($afformSubmission['data'])) {
      $this->populateSubmissionData($afformSubmission['data']);
    }
    else {
      parent::loadEntities();
    }
  }

  /**
   * Load the data from submission table
   */
  protected function populateSubmissionData(array $submissionData) {
    $this->_entityValues = $this->_formDataModel->getEntities();
    foreach ($this->_entityValues as $entity => &$values) {
      foreach ($submissionData as $e => $submission) {
        if ($entity === $e) {
          $values = $submission ?? [];
        }
      }
    }
  }

}
