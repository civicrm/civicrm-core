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
 * Upgrade logic for FiveTwenty
 */
class CRM_Upgrade_Incremental_php_FiveTwenty extends CRM_Upgrade_Incremental_Base {

  /**
   * @var array
   *   api call result keyed on relationship_type.id
   */
  protected static $relationshipTypes;

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
      if (CRM_Core_DAO::checkTableExists('civicrm_persistent') && CRM_Core_DAO::checkTableHasData('civicrm_persistent')) {
        $preUpgradeMessage .= '<br/>' . ts("WARNING: The table \"<code>civicrm_persistent</code>\" is flagged for removal because all official records show it being unused. However, the upgrader has detected data in this copy of \"<code>civicrm_persistent</code>\". Please <a href='%1' target='_blank'>report</a> anything you can about the usage of this table. In the mean-time, the data will be preserved.", [
          1 => 'https://civicrm.org/bug-reporting',
        ]);
      }

      if (CRM_Core_Component::isEnabled('CiviCase')) {
        // Do dry-run to get warning messages.
        $messages = self::_changeCaseTypeLabelToName(TRUE);
        foreach ($messages as $message) {
          $preUpgradeMessage .= "<p>{$message}</p>\n";
        }
      }
    }
  }

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
    if (CRM_Core_Component::isEnabled('CiviCase')) {
      $this->addTask('Change direction of autoassignees in case type xml', 'changeCaseTypeAutoassignee');
      $this->addTask('Change labels back to names in case type xml', 'changeCaseTypeLabelToName');
    }
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add "Template" contribution status', 'templateStatus');
    $this->addTask('Clean up unused table "civicrm_persistent"', 'dropTableIfEmpty', 'civicrm_persistent');
  }

  public static function templateStatus(CRM_Queue_TaskContext $ctx) {
    CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => 'contribution_status',
      'name' => 'Template',
      'label' => ts('Template'),
      'is_active' => TRUE,
      'component_id' => 'CiviContribute',
    ]);
    return TRUE;
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
   * Earlier iterations of this used the api and array manipulation
   * and then another iteration used SimpleXML manipulation, but both
   * suffered from weirdnesses in how conversion back and forth worked.
   *
   * Here we use SQL and a regex. The thing we're changing is pretty
   * well-defined and unique:
   * <default_assignee_relationship>N_b_a</default_assignee_relationship>
   *
   * @return bool
   */
  public static function changeCaseTypeAutoassignee() {
    self::$relationshipTypes = civicrm_api3('RelationshipType', 'get', [
      'options' => ['limit' => 0],
    ])['values'];

    // Get all case types definitions that are using db storage
    $dao = CRM_Core_DAO::executeQuery("SELECT id, definition FROM civicrm_case_type WHERE definition IS NOT NULL AND definition <> ''");
    while ($dao->fetch()) {
      self::processCaseTypeAutoassignee($dao->id, $dao->definition);
    }
    return TRUE;
  }

  /**
   * Process a single case type
   *
   * @param int $caseTypeId
   * @param string $definition
   *   xml string
   */
  public static function processCaseTypeAutoassignee($caseTypeId, $definition) {
    $isDirty = FALSE;
    // find the autoassignees
    preg_match_all('/<default_assignee_relationship>(.*?)<\/default_assignee_relationship>/', $definition, $matches);
    // $matches[1][n] has the text inside the xml tag, e.g. 2_a_b
    foreach ($matches[1] as $index => $match) {
      if (empty($match)) {
        continue;
      }
      // parse out existing id and direction
      [$relationshipTypeId, $direction1] = explode('_', $match);
      // we only care about ones that are b_a
      if ($direction1 === 'b') {
        // we only care about bidirectional
        if (self::isBidirectionalRelationship($relationshipTypeId)) {
          // flip it to be a_b
          // $matches[0][n] has the whole match including the xml tag
          $definition = str_replace($matches[0][$index], "<default_assignee_relationship>{$relationshipTypeId}_a_b</default_assignee_relationship>", $definition);
          $isDirty = TRUE;
        }
      }
    }

    if ($isDirty) {
      $sqlParams = [
        1 => [$definition, 'String'],
        2 => [$caseTypeId, 'Integer'],
      ];
      CRM_Core_DAO::executeQuery("UPDATE civicrm_case_type SET definition = %1 WHERE id = %2", $sqlParams);
      //echo "UPDATE civicrm_case_type SET definition = '" . CRM_Core_DAO::escapeString($sqlParams[1][0]) . "' WHERE id = {$sqlParams[2][0]}\n";
    }
  }

  /**
   * Check if this is bidirectional, based on label. In the situation where
   * we're using this we don't care too much about the edge case where name
   * might not also be bidirectional.
   *
   * @param int $relationshipTypeId
   *
   * @return bool
   */
  private static function isBidirectionalRelationship($relationshipTypeId) {
    if (isset(self::$relationshipTypes[$relationshipTypeId])) {
      if (self::$relationshipTypes[$relationshipTypeId]['label_a_b'] === self::$relationshipTypes[$relationshipTypeId]['label_b_a']) {
        return TRUE;
      }
    }
    return FALSE;
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
   * @param bool $isDryRun
   *   If TRUE then don't actually change anything just report warnings.
   *
   * @return array List of warning messages.
   */
  public static function _changeCaseTypeLabelToName($isDryRun = FALSE) {
    $messages = [];
    self::$relationshipTypes = civicrm_api3('RelationshipType', 'get', [
      'options' => ['limit' => 0],
    ])['values'];

    // Get all case types definitions that are using db storage
    $dao = CRM_Core_DAO::executeQuery("SELECT id FROM civicrm_case_type WHERE definition IS NOT NULL AND definition <> ''");
    while ($dao->fetch()) {
      // array_merge so that existing numeric keys don't get overwritten
      $messages = array_merge($messages, self::_processCaseTypeLabelName($isDryRun, $dao->id));
    }
    return $messages;
  }

  /**
   * Process a single case type for _changeCaseTypeLabelToName()
   *
   * @param bool $isDryRun
   *   If TRUE then don't actually change anything just report warnings.
   * @param int $caseTypeId
   */
  private static function _processCaseTypeLabelName($isDryRun, $caseTypeId) {
    $messages = [];
    $isDirty = FALSE;

    // Get the case type definition
    $caseType = civicrm_api3(
      'CaseType',
      'get',
      ['id' => $caseTypeId]
    )['values'][$caseTypeId];

    foreach ($caseType['definition']['caseRoles'] as $roleSequenceId => $role) {
      // First double-check that there is a unique match on label so we
      // don't get it wrong.
      // There's maybe a fancy way to do this with array_XXX functions but
      // need to take into account edge cases where bidirectional but name
      // is different, or where somehow two labels are the same across types,
      // so do old-fashioned loop.

      $cantConvertMessage = NULL;
      $foundName = NULL;
      foreach (self::$relationshipTypes as $relationshipType) {
        // does it match one of our existing labels
        if ($relationshipType['label_a_b'] === $role['name'] || $relationshipType['label_b_a'] === $role['name']) {
          // So either it's ambiguous, in which case exit loop with a message,
          // or we have the name, so exit loop with that.
          $cantConvertMessage = self::checkAmbiguous($relationshipType, $caseType['name'], $role['name']);
          if (empty($cantConvertMessage)) {
            // not ambiguous, so note the corresponding name for the direction
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
      // Only two possibilities now are we have a name, or we have a message.
      // So the if($foundName) is redundant, but seems clearer somehow.
      if ($foundName && empty($cantConvertMessage)) {
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

    // If this is a dry run during preupgrade checks we can skip this and
    // just return any messages.
    // If for real, then update the case type and here if there's errors
    // we don't really have a choice but to stop the entire upgrade
    // completely. There's no way to just send back messages during a queue
    // run. But we can log a message to error log so that the user has a
    // little more specific info about which case type.
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

    return $messages;
  }

  /**
   * Helper for _processCaseTypeLabelName to check if a label can't be
   * converted unambiguously to name.
   *
   * If it's bidirectional, we can't convert it if there's an edge case
   * where the two names are different.
   *
   * If it's unidirectional, we can't convert it if there's an edge case
   * where there's another type that has the same label.
   *
   * @param array $relationshipType
   * @param string $caseTypeName
   * @param string $xmlRoleName
   *
   * @return string|NULL
   */
  private static function checkAmbiguous($relationshipType, $caseTypeName, $xmlRoleName) {
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
      foreach (self::$relationshipTypes as $innerLoopId => $innerLoopType) {
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

}
