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
class CRM_Core_BAO_Query {

  /**
   * @param CRM_Core_Form $form
   * @param array $extends
   */
  public static function addCustomFormFields(&$form, $extends) {
    $groupDetails = CRM_Core_BAO_CustomGroup::getAll(['extends' => $extends, 'is_active' => TRUE]);
    if ($groupDetails) {
      foreach ($groupDetails as $group) {
        if (empty($group['fields'])) {
          // if there are no searchable fields in the custom group remove it
          // from the details to avoid empty accordians per
          // https://lab.civicrm.org/dev/core/-/issues/5112
          unset($groupDetails[$group['id']]);
        }
        foreach ($group['fields'] as $field) {
          $fieldId = $field['id'];
          $elementName = 'custom_' . $fieldId;
          if ($field['data_type'] === 'Date' && $field['is_search_range']) {
            $form->addDatePickerRange($elementName, $field['label']);
          }
          else {
            CRM_Core_BAO_CustomField::addQuickFormElement($form, $elementName, $fieldId, FALSE, TRUE);
          }
        }
      }
    }
    $tplName = lcfirst($extends[0]) . 'GroupTree';
    $form->assign($tplName, $groupDetails);
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
