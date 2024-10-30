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

namespace Civi\Api4\Action\CiviCase;

/**
 * @inheritDoc
 */
trait CiviCaseSaveTrait {

  /**
   * @param array $items
   * @return array
   */
  protected function write(array $items) {
    $saved = [];
    foreach ($items as $case) {
      $saved[] = $result = \CRM_Case_BAO_Case::create($case);
      // If the case doesn't have an id, it's new & needs to be opened.
      if (empty($case['id'])) {
        $this->openCase($case, $result->id);
      }
    }
    return $saved;
  }

  /**
   * @param $case
   * @param $id
   * @throws \CRM_Core_Exception
   */
  private function openCase($case, $id) {
    // Add case contacts (clients)
    foreach ((array) $case['contact_id'] as $cid) {
      $contactParams = ['case_id' => $id, 'contact_id' => $cid];
      \CRM_Case_BAO_CaseContact::writeRecord($contactParams);
    }

    $caseType = \CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseType', $case['case_type_id'], 'name');

    // Pass "Open Case" params to XML processor
    $xmlProcessor = new \CRM_Case_XMLProcessor_Process();
    $params = [
      'clientID' => $case['contact_id'] ?? NULL,
      'creatorID' => $case['creator_id'] ?? NULL,
      'standardTimeline' => 1,
      'activityTypeName' => 'Open Case',
      'caseID' => $id,
      'subject' => $case['subject'] ?? NULL,
      'location' => $case['location'] ?? NULL,
      'activity_date_time' => $case['start_date'] ?? NULL,
      'duration' => $case['duration'] ?? NULL,
      'medium_id' => $case['medium_id'] ?? NULL,
      'details' => $case['details'] ?? NULL,
      'custom' => [],
      'relationship_end_date' => $case['end_date'] ?? NULL,
    ];

    // Do it! :-D
    $xmlProcessor->run($caseType, $params);
  }

}
