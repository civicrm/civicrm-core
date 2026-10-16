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
class CRM_Utils_Check_Component_OptionGroups extends CRM_Utils_Check_Component {

  /**
   * @return CRM_Utils_Check_Message[]
   */
  public function checkOptionGroupValues() {
    if (CRM_Utils_System::version() !== CRM_Core_BAO_Domain::version()) {
      return [];
    }

    $messages = [];
    $problemValues = [];
    $optionGroups  = civicrm_api3('OptionGroup', 'get', [
      'sequential' => 1,
      'data_type' => ['IS NOT NULL' => 1],
      'options' => ['limit' => 0],
    ]);
    if ($optionGroups['count'] > 0) {
      foreach ($optionGroups['values'] as $optionGroup) {
        $values = CRM_Core_BAO_OptionValue::getOptionValuesArray($optionGroup['id']);
        if (count($values) > 0) {
          foreach ($values as $value) {
            try {
              CRM_Utils_Type::validate($value['value'], $optionGroup['data_type']);
            }
            catch (CRM_Core_Exception $e) {
              $problemValues[] = [
                'group_name' => $optionGroup['title'],
                'value_name' => $value['label'],
              ];
            }
          }
        }
      }
    }
    if (!empty($problemValues)) {
      $rows = '';
      foreach ($problemValues as $problemValue) {
        $rows .= '<tr><td>' . htmlspecialchars($problemValue['group_name']) . '</td><td>' . htmlspecialchars($problemValue['value_name']) . '</td></tr>';
      }

      $messages[] = new CRM_Utils_Check_Message(
       __FUNCTION__,
       ts('The Following Option Values contain value fields that do not match the Data Type of the Option Group')
        . '<table><thead><tr><th>' . ts('Option Group') . '</th><th>' . ts('Option Value') . '</th></tr></thead><tbody>'
        . $rows . '</tbody></table>',
        ts('Option Values with problematic Values'),
        \Psr\Log\LogLevel::NOTICE,
        'fa-server'
      );
    }

    return $messages;
  }

}
