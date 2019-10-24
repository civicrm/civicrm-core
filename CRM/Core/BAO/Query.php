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
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */
class CRM_Core_BAO_Query {

  /**
   * @param CRM_Core_Form $form
   * @param array $extends
   */
  public static function addCustomFormFields(&$form, $extends) {
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $extends);
    if ($groupDetails) {
      $tplName = lcfirst($extends[0]) . 'GroupTree';
      $form->assign($tplName, $groupDetails);
      foreach ($groupDetails as $group) {
        foreach ($group['fields'] as $field) {
          $fieldId = $field['id'];
          $elementName = 'custom_' . $fieldId;
          if ($field['data_type'] == 'Date' && $field['is_search_range']) {
            CRM_Core_Form_Date::buildDateRange($form, $elementName, 1, '_from', '_to', ts('From:'), FALSE);
          }
          else {
            CRM_Core_BAO_CustomField::addQuickFormElement($form, $elementName, $fieldId, FALSE, TRUE);
          }
        }
      }
    }
  }

  /**
   * Get legacy fields which we still maybe support.
   *
   * These are contribution specific but I think it's ok to have one list of legacy supported
   * params in a central place.
   *
   * @return array
   */
  protected static function getLegacySupportedFields(): array {
    // @todo enotices when these are hit so we can start to elimnate them.
    $fieldAliases = [
      'financial_type' => 'financial_type_id',
      'contribution_page' => 'contribution_page_id',
      'payment_instrument' => 'payment_instrument_id',
      // or payment_instrument_id?
      'contribution_payment_instrument' => 'contribution_payment_instrument_id',
      'contribution_status' => 'contribution_status_id',
    ];
    return $fieldAliases;
  }

  /**
   * Getter for the qill object.
   *
   * @return string
   */
  public function qill() {
    return (isset($this->_qill)) ? $this->_qill : "";
  }

  /**
   * Possibly unnecessary function.
   *
   * @param $row
   * @param int $id
   */
  public static function searchAction(&$row, $id) {}

  /**
   * @param $tables
   */
  public static function tableNames(&$tables) {}

  /**
   * Get the name of the field.
   *
   * @param array $values
   *
   * @return string
   */
  protected static function getFieldName($values) {
    $name = $values[0];
    $fieldAliases = self::getLegacySupportedFields();
    if (isset($fieldAliases[$name])) {
      CRM_Core_Error::deprecatedFunctionWarning('These parameters should be standardised before we get here');
      return $fieldAliases[$name];
    }

    return str_replace(['_high', '_low'], '', $name);
  }

}
