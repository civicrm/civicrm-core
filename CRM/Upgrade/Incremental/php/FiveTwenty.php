<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Upgrade logic for FiveTwenty */
class CRM_Upgrade_Incremental_php_FiveTwenty extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed beforeupgrade.
   *
   * Note: This function is called iteratively for each upcoming
   * revision to the database.
   *
   * @param string $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'.
   * @param null $currentVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev == '5.20.alpha1') {
      $config = CRM_Core_Config::singleton();
      if (in_array('CiviCase', $config->enableComponents)) {
        // Do dry-run to get warning messages.
        $messages = self::_changeCaseTypeLabelToName(TRUE);
        foreach ($messages as $message) {
          $preUpgradeMessage .= "<p>{$message}</p>\n";
        }
      }
    }
  }

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    // Example: Generate a post-upgrade message.
    // if ($rev == '5.12.34') {
    //   $postUpgradeMessage .= '<br /><br />' . ts("By default, CiviCRM now disables the ability to import directly from SQL. To use this feature, you must explicitly grant permission 'import SQL datasource'.");
    // }
  }

  /*
   * Important! All upgrade functions MUST add a 'runSql' task.
   * Uncomment and use the following template for a new upgrade version
   * (change the x in the function name):
   */

  // public static function taskFoo(CRM_Queue_TaskContext $ctx, ...) {
  //   return TRUE;
  // }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_20_alpha1($rev) {
    $this->addTask('Add frontend title column to contribution page table', 'addColumn', 'civicrm_contribution_page',
      'frontend_title', "varchar(255) DEFAULT NULL COMMENT 'Contribution Page Public title'", TRUE, '5.20.alpha1');
    $this->addTask('Add is_template field to civicrm_contribution', 'addColumn', 'civicrm_contribution', 'is_template',
      "tinyint(4) DEFAULT '0' COMMENT 'Shows this is a template for recurring contributions.'", FALSE, '5.20.alpha1');
    $this->addTask('Add order_reference field to civicrm_financial_trxn', 'addColumn', 'civicrm_financial_trxn', 'order_reference',
      "varchar(255) COMMENT 'Payment Processor external order reference'", FALSE, '5.20.alpha1');
    $config = CRM_Core_Config::singleton();
    if (in_array('CiviCase', $config->enableComponents)) {
      $this->addTask('Change labels back to names in case type xml', 'changeCaseTypeLabelToName');
      $this->addTask('Change direction of autoassignees in case type xml', 'changeCaseTypeAutoassignee');
    }
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
  }

  /**
   * Change labels in case type xml definition back to names. (dev/core#1046)
   * ONLY for ones using database storage - don't want to "fork" case types
   * that aren't currently forked.
   *
   * @return bool
   */
  public static function changeCaseTypeLabelToName() {
    self::_changeCaseTypeLabelToName(FALSE);
    return TRUE;
  }

  /**
   * Change labels in case type xml definition back to names. (dev/core#1046)
   * ONLY for ones using database storage - don't want to "fork" case types
   * that aren't currently forked.
   *
   * @param $isDryRun bool
   *   If TRUE then don't actually change anything just report warnings.
   *
   * @return array List of warning messages.
   */
  private static function _changeCaseTypeLabelToName($isDryRun = FALSE) {
    $messages = [];
    $caseTypes = civicrm_api3('CaseType', 'get', [
      'options' => ['limit' => 0],
    ])['values'];
    $relationshipTypes = civicrm_api3('RelationshipType', 'get', [
      'options' => ['limit' => 0],
    ])['values'];

    foreach ($caseTypes as $caseType) {
      if (self::isExternalXMLFileNotInDatabase($caseType['id'])) {
        // Don't process case types that don't use the db.
        continue;
      }

      $isDirty = FALSE;

      foreach ($caseType['definition']['caseRoles'] as $roleSequenceId => $role) {
        // First double-check that there is a unique match on label so we
        // don't get it wrong.
        // There's maybe a fancy way to do this with array_XXX functions but
        // need to take into account edge cases where bidirectional but name
        // is different, or where somehow two labels are the same across types,
        // so do old-fashioned loop.

        $cantConvertMessage = NULL;
        $foundName = NULL;
        foreach ($relationshipTypes as $relationshipType) {
          // does it match one of our existing labels
          if ($relationshipType['label_a_b'] === $role['name'] || $relationshipType['label_b_a'] === $role['name']) {
            // So either it's ambiguous, in which case exit loop with a message,
            // or we have the name, so exit loop with that.
            $cantConvertMessage = self::checkAmbiguous($relationshipTypes, $relationshipType, $caseType['name'], $role['name']);
            if (empty($cantConvertMessage)) {
              $foundName = ($relationshipType['label_a_b'] === $role['name']) ? $relationshipType['name_a_b'] : $relationshipType['name_b_a'];
            }
            break;
          }
        }

        if (empty($foundName) && empty($cantConvertMessage)) {
          // It's possible we went through all relationship types and didn't
          // find any match, so don't change anything.
          $cantConvertMessage = ts("Case Type '%1', role '%2' doesn't seem to be a valid role. See the administration console status messages for more info.", [
            1 => htmlspecialchars($caseType['name']),
            2 => htmlspecialchars($role['name']),
          ]);
        }
        // This has an implicit check that we found a name since if we didn't
        // we'd have a message from the if just above.
        if (empty($cantConvertMessage)) {
          // If name and label are the same don't need to update anything.
          if ($foundName !== $role['name']) {
            $caseType['definition']['caseRoles'][$roleSequenceId]['name'] = $foundName;
            $isDirty = TRUE;
          }
        }
        else {
          $messages[] = $cantConvertMessage;
        }

        // end looping thru all roles in definition
      }

      if ($isDirty && !$isDryRun) {
        $exception = NULL;
        try {
          $api_result = civicrm_api3('CaseType', 'create', $caseType);
        }
        catch (Exception $e) {
          $exception = $e;
          $errorMessage = ts("Error updating case type '%1': %2", [
            1 => htmlspecialchars($caseType['name']),
            2 => htmlspecialchars($e->getMessage()),
          ]);
          CRM_Core_Error::debug_log_message($errorMessage);
        }
        if (!empty($api_result['is_error'])) {
          $errorMessage = ts("Error updating case type '%1': %2", [
            1 => htmlspecialchars($caseType['name']),
            2 => htmlspecialchars($api_result['error_message']),
          ]);
          CRM_Core_Error::debug_log_message($errorMessage);
          $exception = new Exception($errorMessage);
        }
        // We need to rethrow the error which unfortunately stops the
        // entire upgrade including any further tasks. But otherwise
        // the only way to notify the user something went wrong is with a
        // crazy workaround.
        if ($exception) {
          throw $exception;
        }
      }

      // end looping through all case types
    }
    return $messages;
  }

  /**
   * Helper for changeCaseTypeLabelToName
   *
   * @param $relationshipTypes array
   * @param $relationshipType array
   * @param $caseTypeName string
   * @param $xmlRoleName string
   *
   * @return string|NULL
   */
  private static function checkAmbiguous($relationshipTypes, $relationshipType, $caseTypeName, $xmlRoleName) {
    $cantConvertMessage = NULL;
    if ($relationshipType['label_a_b'] === $relationshipType['label_b_a']) {
      // bidirectional, so check if names are different for some reason
      if ($relationshipType['name_a_b'] !== $relationshipType['name_b_a']) {
        $cantConvertMessage = ts("Case Type '%1', role '%2' has an ambiguous configuration and can't be automatically updated. See the administration console status messages for more info.", [
          1 => htmlspecialchars($caseTypeName),
          2 => htmlspecialchars($xmlRoleName),
        ]);
      }
    }
    else {
      // Check if it matches either label_a_b or label_b_a for another type
      foreach ($relationshipTypes as $innerLoopId => $innerLoopType) {
        if ($innerLoopId == $relationshipType['id']) {
          // Only check types that aren't the same one we're on.
          // Sidenote: The loop index is integer but the 'id' member is string
          continue;
        }
        if ($innerLoopType['label_a_b'] === $xmlRoleName || $innerLoopType['label_b_a'] === $xmlRoleName) {
          $cantConvertMessage = ts("Case Type '%1', role '%2' has an ambiguous configuration where the role matches multiple labels and so can't be automatically updated. See the administration console status messages for more info.", [
            1 => htmlspecialchars($caseTypeName),
            2 => htmlspecialchars($xmlRoleName),
          ]);
          break;
        }
      }
    }
    return $cantConvertMessage;
  }

  /**
   * Change direction of activity autoassignees in case type xml for
   * bidirectional relationship types if they point the other way. This is
   * mostly a visual issue on the case type edit screen and doesn't affect
   * normal operation, but could lead to confusion. (dev/core#1046)
   * ONLY for ones using database storage - don't want to "fork" case types
   * that aren't currently forked.
   *
   * @return bool
   */
  public static function changeCaseTypeAutoassignee() {
    $caseTypes = civicrm_api3('CaseType', 'get', [
      'options' => ['limit' => 0],
    ])['values'];
    $relationshipTypes = civicrm_api3('RelationshipType', 'get', [
      'options' => ['limit' => 0],
    ])['values'];

    foreach ($caseTypes as $caseType) {
      if (self::isExternalXMLFileNotInDatabase($caseType['id'])) {
        // Don't process case types that don't use the db.
        continue;
      }

      $isDirty = FALSE;

      // loop through each ActivitySet in the xml to find the autoassignees
      foreach ($caseType['definition']['activitySets'] as $activitySetSequenceId => $activitySet) {
        if (isset($activitySet['activityTypes'])) {
          foreach ($activitySet['activityTypes'] as $activityTypeSequenceId => $activityType) {
            // does this one have an autoassignee?
            if (!empty($activityType['default_assignee_relationship'])) {
              // it's in format e.g. 2_a_b, so parse out
              list($relationshipTypeId, $direction1) = explode('_', $activityType['default_assignee_relationship']);
              // we only care about ones that are b_a
              if ($direction1 === 'b') {
                if (self::isBidirectionalRelationship($relationshipTypeId, $relationshipTypes)) {
                  // flip it to be a_b
                  $caseType['definition']['activitySets'][$activitySetSequenceId]['activityTypes'][$activityTypeSequenceId]['default_assignee_relationship'] = "{$relationshipTypeId}_a_b";
                  $isDirty = TRUE;
                }
              }
            }
          }
        }
      }

      if ($isDirty) {
        $exception = NULL;
        try {
          $api_result = civicrm_api3('CaseType', 'create', $caseType);
        }
        catch (Exception $e) {
          $exception = $e;
          $errorMessage = ts("Error updating case type '%1': %2", [
            1 => htmlspecialchars($caseType['name']),
            2 => htmlspecialchars($e->getMessage()),
          ]);
          CRM_Core_Error::debug_log_message($errorMessage);
        }
        if (!empty($api_result['is_error'])) {
          $errorMessage = ts("Error updating case type '%1': %2", [
            1 => htmlspecialchars($caseType['name']),
            2 => htmlspecialchars($api_result['error_message']),
          ]);
          CRM_Core_Error::debug_log_message($errorMessage);
          $exception = new Exception($errorMessage);
        }
        // We need to rethrow the error which unfortunately stops the
        // entire upgrade including any further tasks. But otherwise
        // the only way to notify the user something went wrong is with a
        // crazy workaround.
        if ($exception) {
          throw $exception;
        }
      }

      // end looping through all case types
    }
    return TRUE;
  }

  /**
   * Check if this is bidirectional, based on label. In the situation where
   * we're using this we don't care too much about the edge case where name
   * might not also be bidirectional.
   *
   * @param $relationshipTypeId int
   * @param $relationshipTypes array
   *   Keyed by relationship_type.id
   *
   * @return bool
   */
  private static function isBidirectionalRelationship($relationshipTypeId, $relationshipTypes) {
    if (isset($relationshipTypes[$relationshipTypeId])) {
      if ($relationshipTypes[$relationshipTypeId]['label_a_b'] === $relationshipTypes[$relationshipTypeId]['label_b_a']) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * The existing functions regarding "forkable/forked" don't tell us
   * what we want here. We want to know if the definition in the db is
   * empty. Steal just one line from CRM_Case_BAO_CaseType::isForked().
   *
   * @param $caseTypeId int
   *
   * @return bool
   */
  private static function isExternalXMLFileNotInDatabase($caseTypeId) {
    $dbDefinition = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseType', $caseTypeId, 'definition', 'id', TRUE);
    if (empty($dbDefinition)) {
      return TRUE;
    }
    return FALSE;
  }

}
