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
   * @return array
   */
  public function checkIndices() {
    $messages = [];

    // CRM-21298: The "Update Indices" tool that this check suggests is
    // unreliable. Bypass this check until CRM-20817 and CRM-20533 are resolved.
    return $messages;

    $missingIndices = CRM_Core_BAO_SchemaHandler::getMissingIndices();
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

}
