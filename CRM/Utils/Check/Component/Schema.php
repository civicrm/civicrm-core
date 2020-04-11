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
   * @return array
   * @throws \CiviCRM_API3_Exception
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
      $message = "<p>The following tables have missing indices. Click 'Update Indices' button to create them.<p>
        <p><table><thead><tr><th>Table Name</th><th>Key Name</th><th>Expected Indices</th>
        </tr></thead><tbody>
        $html
        </tbody></table></p>";
      $msg = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts($message),
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
   * @return array
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
   * @return array
   */
  public function checkSmartGroupCustomFieldCriteria() {
    $messages = $problematicSG = [];
    $customFieldIds = array_keys(CRM_Core_BAO_CustomField::getFields('ANY', FALSE, FALSE, NULL, NULL, FALSE, FALSE, FALSE));
    try {
      $smartGroups = civicrm_api3('SavedSearch', 'get', [
        'sequential' => 1,
        'options' => ['limit' => 0],
      ]);
    }
    catch (CiviCRM_API3_Exception $e) {
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
      foreach ($group['form_values'] as $formValues) {
        if (isset($formValues[0]) && (strpos($formValues[0], 'custom_') === 0)) {
          list(, $customFieldID) = explode('_', $formValues[0]);
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
            $fieldName = ts('<a href="%1" title="Edit Custom Field"> %2 </a>', [
              1 => CRM_Utils_System::url('civicrm/admin/custom/group/field/update',
                "action=update&reset=1&gid={$customField['custom_group_id']}&id={$field['cfid']}", TRUE
              ),
              2 => $customField['label'],
            ]);
          }
          catch (CiviCRM_API3_Exception $e) {
            $fieldName = ' <span style="color:red"> - Deleted - </span> ';
          }
        }
        $groupEdit = '<a href="' . CRM_Utils_System::url('civicrm/contact/search/advanced', "?reset=1&ssID={$field['ssid']}", TRUE) . '" title="' . ts('Edit search criteria') . '"> <i class="crm-i fa-pencil"></i> </a>';
        $groupConfig = '<a href="' . CRM_Utils_System::url('civicrm/group', "?reset=1&action=update&id={$id}", TRUE) . '" title="' . ts('Group settings') . '"> <i class="crm-i fa-gear"></i> </a>';
        $html .= "<tr><td>{$id} - {$field['title']} </td><td>{$groupEdit} {$groupConfig}</td><td class='disabled'>{$fieldName}</td>";
      }

      $message = "<p>The following smart groups include custom fields which are disabled/deleted from the database. This may cause errors on group page.
        You might need to edit their search criteria and update them to clean outdated fields from saved search OR disable them in order to fix the error.</p>
        <p><table><thead><tr><th>Group</th><th></th><th>Custom Field</th>
        </tr></thead><tbody>
        $html
        </tbody></table></p>
       ";

      $msg = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts($message),
        ts('Disabled/Deleted fields on Smart Groups'),
        \Psr\Log\LogLevel::WARNING,
        'fa-server'
      );
      $messages[] = $msg;
    }
    return $messages;
  }

}
