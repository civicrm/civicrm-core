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
    // Example: Generate a pre-upgrade message.
    // if ($rev == '5.12.34') {
    //   $preUpgradeMessage .= '<p>' . ts('A new permission, "%1", has been added. This permission is now used to control access to the Manage Tags screen.', array(1 => ts('manage tags'))) . '</p>';
    // }
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
      $this->addTask('Change direction of autoassignees in case type xml', 'changeCaseTypeAutoassignee');
    }
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
  }

  /**
   * Change direction of activity autoassignees in case type xml for
   * bidirectional relationship types if they point the other way. This is
   * mostly a visual issue on the case type edit screen and doesn't affect
   * normal operation, but could lead to confusion and a future mixup.
   * (dev/core#1046)
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
        self::fixCaseTypeDefinitionArrays($caseType);
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

  /**
   * There's an issue where the value returned from api CaseType.get
   * can't be used directly as input to CaseType.create because an xml element
   * that is empty is StdObject {} which then comes out as [], which then
   * during the create triggers a warning for htmlspecialchars when it tries to
   * convert it back to xml. Because most of the xml elements are not blank,
   * this only really comes up for items under
   * <ActivitySet><ActivityTypes><ActivityType>
   * where there are items like <default_assignee_contact> that might be
   * present but empty.
   *
   * @param $caseType array
   */
  private static function fixCaseTypeDefinitionArrays(&$caseType) {
    foreach ($caseType['definition']['activitySets'] as $activitySetSequenceId => $activitySet) {
      if (isset($activitySet['activityTypes'])) {
        foreach ($activitySet['activityTypes'] as $activityTypeSequenceId => $activityType) {
          foreach ($activityType as $key => $value) {
            if (is_array($value) && empty($value)) {
              // convert empty arrays to the empty string
              $caseType['definition']['activitySets'][$activitySetSequenceId]['activityTypes'][$activityTypeSequenceId][$key] = '';
            }
          }
        }
      }
    }
  }

}
