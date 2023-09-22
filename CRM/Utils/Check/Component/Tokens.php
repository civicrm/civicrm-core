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
class CRM_Utils_Check_Component_Tokens extends CRM_Utils_Check_Component {

  /**
   * Check that deprecated and / or tokens no longer exist/
   *
   * @return CRM_Utils_Check_Message[]
   */
  public function checkTokens(): array {
    $changes = CRM_Utils_Token::getTokenDeprecations();
    $messages = $problems = [];
    foreach ($changes['WorkFlowMessageTemplates'] as $workflowName => $workflowChanges) {
      foreach ($workflowChanges as $old => $new) {
        if (CRM_Core_DAO::singleValueQuery("
          SELECT COUNT(*)
          FROM civicrm_msg_template
          WHERE workflow_name = '$workflowName'
          AND (
            msg_html LIKE '%$old%'
            OR msg_subject LIKE '%$old%'
            OR civicrm_msg_template.msg_text LIKE '%$old%'
          )
        ")) {
          $problems[] = ts('Please review your %1 message template and remove references to the token %2 as it has been replaced by %3', [
            1 => $workflowName,
            2 => '{' . $old . '}',
            3 => '{' . $new . '}',
          ]);
        }
      }
    }
    if (!empty($problems)) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__ . md5(implode(',', $problems)),
        '<p>' .
        ts('You are using tokens that have been removed or deprecated.') .
        '</p>' .
        '<ul><li>' .
        implode('</li><li>', $problems) .
        '</li></ul></p>',
        ts('Outdated tokens in use'),
        \Psr\Log\LogLevel::WARNING
      );
    }
    return $messages;
  }

}
