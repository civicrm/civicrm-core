<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Utils_Check_Component_Schema extends CRM_Utils_Check_Component {

  /**
   * @return array
   */
  public function checkIndices() {
    $messages = array();
    list($missingIndices, $existingKeyIndices) = CRM_Core_BAO_SchemaHandler::getMissingIndices();
    if ($existingKeyIndices) {
      $html = '';
      foreach ($existingKeyIndices as $tableName => $indices) {
        foreach ($indices as $index) {
          $fields = implode(', ', $index['field']);
          $html .= "<tr><td>{$tableName}</td><td>{$index['name']}</td><td>$fields</td>";
        }
      }
      $keyMessage = "<p>The following tables have an index key with a mismatch in value. Please delete the key indices listed from the below table and then click on 'Update Indices' button. <p>
        <p><table><thead><tr><th>Table Name</th><th>Key Name</th><th>Fields</th>
        </tr></thead><tbody>
        $html
        </tbody></table></p>";
    }
    if ($missingIndices || $existingKeyIndices) {
      $message = "You have missing indices on some tables. This may cause poor performance.";
      if (!empty($keyMessage)) {
        $message = $keyMessage;
        $message .= ts("If you are unsure how to perform this action or do not know what to do please contact your system administrator for assistance");
      }
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
        array('System', 'updateindexes')
      );
      $messages[] = $msg;
    }
    return $messages;
  }

}
