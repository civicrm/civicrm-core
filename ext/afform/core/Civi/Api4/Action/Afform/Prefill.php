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
    parent::loadEntities();
    if (!empty($afformSubmission['data'])) {
      $this->populateSubmissionData($afformSubmission['data']);
    }
  }

  /**
   * Load the data from submission table
   */
  protected function populateSubmissionData(array $submissionData) {
    $submissionData = $this->filterDraftValues($submissionData);
    foreach ($submissionData as $entityName => $records) {
      if (!is_array($records)) {
        continue;
      }
      foreach ($records as $index => $record) {
        if (!is_array($record)) {
          continue;
        }
        $this->_entityValues[$entityName][$index] ??= ['fields' => []];
        if (isset($record['fields']) && is_array($record['fields'])) {
          $this->_entityValues[$entityName][$index]['fields'] = array_merge(
            $this->_entityValues[$entityName][$index]['fields'] ?? [],
            $record['fields']
          );
        }
        if (isset($record['extras']) && is_array($record['extras'])) {
          $this->_entityValues[$entityName][$index]['extras'] = array_merge(
            $this->_entityValues[$entityName][$index]['extras'] ?? [],
            $record['extras']
          );
        }
        if (isset($record['joins']) && is_array($record['joins'])) {
          $this->_entityValues[$entityName][$index]['joins'] ??= [];
          foreach ($record['joins'] as $joinEntity => $joinRecords) {
            if (!is_array($joinRecords)) {
              continue;
            }
            $this->_entityValues[$entityName][$index]['joins'][$joinEntity] ??= [];
            foreach ($joinRecords as $joinIndex => $joinRecord) {
              if (is_array($joinRecord)) {
                $this->_entityValues[$entityName][$index]['joins'][$joinEntity][$joinIndex] = array_merge(
                  $this->_entityValues[$entityName][$index]['joins'][$joinEntity][$joinIndex] ?? [],
                  $joinRecord
                );
              }
            }
          }
        }
      }
    }
  }

}
