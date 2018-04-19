<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Utils_Check_Component_OptionGroups extends CRM_Utils_Check_Component {

  /**
   * @return array
   */
  public function checkOptionGroupValues() {
    $messages = array();
    $problemValues = array();
    $optionGroups  = civicrm_api3('OptionGroup', 'get', array(
      'sequential' => 1,
      'data_type' => array('IS NOT NULL' => 1),
      'options' => array('limit' => 0),
    ));
    if ($optionGroups['count'] > 0) {
      foreach ($optionGroups['values'] as $optionGroup) {
        $values = CRM_Core_BAO_OptionValue::getOptionValuesArray($optionGroup['id']);
        if (count($values) > 0) {
          foreach ($values as $value) {
            $validate = CRM_Utils_Type::validate($value['value'], $optionGroup['data_type'], FALSE);
            if (!$validate) {
              $problemValues[] = array(
                'group_name' => $optionGroup['title'],
                'value_name' => $value['label'],
              );
            }
          }
        }
      }
    }
    if (!empty($problemValues)) {
      $strings = '';
      foreach ($problemValues as $problemValue) {
        $strings .= ts('<tr><td> "%1" </td><td> "%2" </td></tr>', array(
          1 => $problemValue['group_name'],
          2 => $problemValue['value_name'],
        ));
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
