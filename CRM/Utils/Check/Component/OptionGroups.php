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
      $strings = '';
      foreach ($problemValues as $problemValue) {
        $strings .= ts('<tr><td> "%1" </td><td> "%2" </td></tr>', [
          1 => $problemValue['group_name'],
          2 => $problemValue['value_name'],
        ]);
      }

      $messages[] = new CRM_Utils_Check_Message(
       __FUNCTION__,
       ts('The Following Option Values contain value fields that do not match the Data Type of the Option Group</p>
        <p><table><tbody><th>Option Group</th><th>Option Value</th></tbody><tbody>') .
        $strings . ts('</tbody></table></p>'),
        ts('Option Values with problematic Values'),
        \Psr\Log\LogLevel::NOTICE,
        'fa-server'
      );
    }

    return $messages;
  }

}
