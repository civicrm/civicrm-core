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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Check_Component_Schema extends CRM_Utils_Check_Component {

  /**
   * Check defined indices exist.
   *
   * @return CRM_Utils_Check_Message[]
   * @throws \CRM_Core_Exception
   */
  public function checkIndices() {
    $messages = [];

    // CRM-21298: The "Update Indices" tool that this check suggests is
    // unreliable. Bypass this check until CRM-20817 and CRM-20533 are resolved.
    return $messages;

    $missingIndices = civicrm_api3('System', 'getmissingindices', [])['values'];
    if ($missingIndices) {
      $html = '';
      foreach ($missingIndices as $tableName => $indices) {
        foreach ($indices as $index) {
          $fields = implode(', ', $index['field']);
          $html .= "<tr><td>{$tableName}</td><td>{$index['name']}</td><td>$fields</td>";
        }
      }
      $message = '<p>' . ts("The following tables have missing indices. Click 'Update Indices' button to create them.") . '<p>'
        . '<p><table><thead><tr><th>' . ts('Table Name') . '</th><th>' . ts('Key Name') . '</th><th>' . ts('Expected Indices') . '</th>'
        . '</tr></thead><tbody>'
        . $html
        . '</tbody></table></p>';
      $msg = new CRM_Utils_Check_Message(
        __FUNCTION__,
        $message,
        ts('Performance warning: Missing indices'),
        \Psr\Log\LogLevel::WARNING,
        'fa-server'
      );
      $msg->addAction(
        ts('Update Indices'),
        ts('Update all database indices now? This may take a few minutes and cause a noticeable performance lag for all users while running.'),
        'api3',
        ['System', 'updateindexes']
      );
      $messages[] = $msg;
    }
    return $messages;
  }

  /**
   * @return CRM_Utils_Check_Message[]
   */
  public function checkMissingLogTables() {
    $messages = [];
    $logging = new CRM_Logging_Schema();
    $missingLogTables = $logging->getMissingLogTables();

    if (Civi::settings()->get('logging') && $missingLogTables) {
      $msg = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts("You don't have logging enabled on some tables. This may cause errors on performing insert/update operation on them."),
        ts('Missing Log Tables'),
        \Psr\Log\LogLevel::WARNING,
        'fa-server'
      );
      $msg->addAction(
        ts('Create Missing Log Tables'),
        ts('Create missing log tables now? This may take few minutes.'),
        'api3',
        ['System', 'createmissinglogtables']
      );
      $messages[] = $msg;
    }
    return $messages;
  }

  /**
   * Check that no smart groups exist that contain deleted custom fields.
   *
   * @return CRM_Utils_Check_Message[]
   */
  public function checkSmartGroupCustomFieldCriteria() {
    if (CRM_Core_BAO_Domain::isDBUpdateRequired()) {
      // Do not run this check when the db has not been updated as it might fail on non-updated schema issues.
      return [];
    }
    $messages = $problematicSG = [];
    $customFieldIds = array_keys(CRM_Core_BAO_CustomField::getFields('ANY', FALSE, FALSE, NULL, NULL, FALSE, FALSE, FALSE));
    try {
      $smartGroups = civicrm_api3('SavedSearch', 'get', [
        'sequential' => 1,
        'options' => ['limit' => 0],
      ]);
    }
    catch (CRM_Core_Exception $e) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('The smart group check was unable to run. This is likely to because a database upgrade is pending.'),
        ts('Smart Group check did not run'),
        \Psr\Log\LogLevel::INFO,
        'fa-server'
      );
      return $messages;
    }
    if (empty($smartGroups['values'])) {
      return $messages;
    }
    foreach ($smartGroups['values'] as $group) {
      if (empty($group['form_values'])) {
        continue;
      }
      foreach ($group['form_values'] as $key => $val) {
        if (str_starts_with($key, 'custom_')) {
          [, $customFieldID] = explode('_', $key);
          if (!in_array((int) $customFieldID, $customFieldIds, TRUE)) {
            $problematicSG[CRM_Contact_BAO_SavedSearch::getName($group['id'], 'id')] = [
              'title' => CRM_Contact_BAO_SavedSearch::getName($group['id'], 'title'),
              'cfid' => $customFieldID,
              'ssid' => $group['id'],
            ];
          }
        }
      }
    }

    if (!empty($problematicSG)) {
      $html = '';
      foreach ($problematicSG as $id => $field) {
        if (!empty($field['cfid'])) {
          try {
            $customField = civicrm_api3('CustomField', 'getsingle', [
              'sequential' => 1,
              'id' => $field['cfid'],
            ]);
            $url = CRM_Utils_System::url('civicrm/admin/custom/group/field/update', "action=update&reset=1&gid={$customField['custom_group_id']}&id={$field['cfid']}", TRUE);
            $fieldName = '<a href="' . $url . '" title="' . ts('Edit Custom Field', ['escape' => 'htmlattribute']) . '">' . $customField['label'] . '</a>';
          }
          catch (CRM_Core_Exception $e) {
            $fieldName = '<span style="color:red">' . ts('Deleted') . ' - ' . ts('Field ID %1', [1 => $field['cfid']]) . '</span> ';
          }
        }
        $groupEdit = '<a href="' . CRM_Utils_System::url('civicrm/contact/search/advanced', "reset=1&ssID={$field['ssid']}", TRUE) . '" title="' . ts('Edit search criteria', ['escape' => 'htmlattribute']) . '"> <i class="crm-i fa-pencil" aria-hidden="true"></i> </a>';
        $groupConfig = '<a href="' . CRM_Utils_System::url('civicrm/group/edit', "reset=1&action=update&id={$id}", TRUE) . '" title="' . ts('Group settings', ['escape' => 'htmlattribute']) . '"> <i class="crm-i fa-gear" aria-hidden="true"></i> </a>';
        $html .= "<tr><td>{$id} - {$field['title']} </td><td>{$groupEdit} {$groupConfig}</td><td class='disabled'>{$fieldName}</td>";
      }

      $message = "<p>" . ts('The following smart groups include custom fields which are disabled or deleted from the database. Missing fields should automatically be ignored from the smart group criteria, but you may want to review and update their search criteria to remove the outdated fields.') . '</p>'
        . '<p><table><thead><tr><th>' . ts('Group') . '</th><th></th><th>' . ts('Custom Field') . '</th>'
        . '</tr></thead><tbody>'
        . $html
        . '</tbody></table></p>';

      $msg = new CRM_Utils_Check_Message(
        __FUNCTION__,
        $message,
        ts('Disabled/Deleted fields on Smart Groups'),
        \Psr\Log\LogLevel::WARNING,
        'fa-server'
      );
      $messages[] = $msg;
    }
    return $messages;
  }

  /**
   * The column 'civicrm_activity.original_id' should not have 'ON DELETE CASCADE'.
   * It is OK to have 'ON DELETE SET NULL' or to have no constraint.
   *
   * @return CRM_Utils_Check_Message[]
   */
  public function checkOldAcitvityCascade(): array {
    $messages = [];

    $sql = "SELECT CONSTRAINT_NAME, DELETE_RULE
      FROM information_schema.referential_constraints
      WHERE CONSTRAINT_SCHEMA=database() AND TABLE_NAME='civicrm_activity' AND CONSTRAINT_NAME='FK_civicrm_activity_original_id'
    ";
    $cascades = CRM_Core_DAO::executeQuery($sql, [], FALSE, NULL, FALSE, FALSE)
      ->fetchMap('CONSTRAINT_NAME', 'DELETE_RULE');
    $cascade = $cascades['FK_civicrm_activity_original_id'] ?? NULL;
    if ($cascade === 'CASCADE') {
      $docUrl = 'https://civicrm.org/redirect/activities-5.57';
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts(
          '<p>The table <code>%1</code> includes an incorrect constraint. <a %2>Learn how to fix this.</a>', [
            1 => 'civicrm_activity',
            2 => 'target="_blank" href="' . htmlentities($docUrl) . '"',
          ]
        ),
        ts('Schema Error'),
        \Psr\Log\LogLevel::WARNING,
        'fa-server'
      );
    }

    return $messages;
  }

  /**
   * @return CRM_Utils_Check_Message[]
   */
  public function checkMoneyValueFormatConfig() {
    $messages = [];
    if (CRM_Core_Config::singleton()->moneyvalueformat !== '%!i') {
      $msg = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts(
          '<p>The Monetary Value Display format is a deprecated setting, and this site has a non-standard format. Please report your configuration on <a href="%1">this Gitlab issue</a>.',
          [1 => 'https://lab.civicrm.org/dev/core/-/issues/1494']
        ),
        ts('Deprecated monetary value display format configuration'),
        \Psr\Log\LogLevel::WARNING,
        'fa-server'
      );
      $messages[] = $msg;
    }
    return $messages;
  }

  /**
   * Check the function to populate phone_numeric exists.
   *
   * @return array|\CRM_Utils_Check_Message[]
   */
  public function checkPhoneFunctionExists():array {
    $dao = CRM_Core_DAO::executeQuery("SHOW function status WHERE db = database() AND name = 'civicrm_strip_non_numeric'");
    if (!$dao->fetch()) {
      $msg = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts("Your database is missing a function to populate the 'Phone number' field with a numbers-only version of the phone."),
        ts('Missing Phone numeric function'),
        \Psr\Log\LogLevel::WARNING,
        'fa-server'
      );
      $msg->addAction(
        ts('Rebuild triggers (also re-builds the phone number function)'),
        ts('Create missing function now? This may take a few minutes.'),
        'api3',
        ['System', 'flush', ['triggers' => TRUE]]
      );
      return [$msg];
    }
    return [];
  }

  /**
   * Check the SQL trigger to populate `civicrm_relationship_cache` exists.
   *
   * @return array|\CRM_Utils_Check_Message[]
   */
  public function checkRelationshipCacheTriggers():array {
    if (\Civi::settings()->get('logging_no_trigger_permission')) {
      // The mysql user does not have permission to view whether the trigger exists.
      return [];
    }
    $dao = CRM_Core_DAO::executeQuery("SHOW TRIGGERS WHERE (`Table` = 'civicrm_relationship' OR `Table` = 'civicrm_relationship_type') AND `Statement` LIKE '%civicrm_relationship_cache%';");
    if ($dao->N !== 3) {
      $msg = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts("Your database is missing functionality to populate the relationship cache."),
        ts('Missing Relationship Cache Trigger'),
        \Psr\Log\LogLevel::WARNING,
        'fa-database'
      );
      $msg->addAction(
        ts('Rebuild triggers'),
        ts('Create missing triggers now? This may take a few minutes.'),
        'api3',
        ['System', 'flush', ['triggers' => TRUE]]
      );
      return [$msg];
    }
    return [];
  }

  /**
   * Verify `civicrm_relationship_cache` table contains the right amount of data.
   *
   * @return array|\CRM_Utils_Check_Message[]
   */
  public function checkRelationshipCacheData(): array {
    $relationshipCount = (int) CRM_Core_DAO::singleValueQuery("SELECT COUNT(`id`) FROM `civicrm_relationship`");
    $cacheCount = (int) CRM_Core_DAO::singleValueQuery("SELECT COUNT(`id`) FROM `civicrm_relationship_cache`");
    $expectedCount = 2 * $relationshipCount;
    if ($cacheCount !== $expectedCount) {
      $msg = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts("Your database is missing relationship cache data; this can cause related contact information to not show when it should.") .
          '<ul><li>' . ts('Expected %1 records.', [1 => $expectedCount]) . '</li>' .
          '<li>' . ts('Found %1 in cache.', [1 => $cacheCount]) . '</li></ul>',
        ts('Missing Relationship Cache Data'),
        \Psr\Log\LogLevel::WARNING,
        'fa-database'
      );
      $msg->addAction(
        ts('Rebuild cache'),
        '<p>' . ts('Rebuild relationship cache now? This may take a few minutes.') . '</p>' .
        '<p>' . ts('Note: on very large databases it may be necessary to run this via cli instead to avoid timeouts:') . '</p>' .
        '<pre>cv api4 RelationshipCache.rebuild</pre>',
        'api4',
        ['RelationshipCache', 'rebuild']
      );
      return [$msg];
    }
    return [];
  }

}
