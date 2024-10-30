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
 * @copyright U.S. PIRG Education Fund 2007
 *
 */

require_once 'HTML/QuickForm/advmultiselect.php';

/**
 * Class CRM_Core_QuickForm_NestedAdvMultiSelect
 */
class CRM_Core_QuickForm_NestedAdvMultiSelect extends HTML_QuickForm_advmultiselect {

  /**
   * Loads options from different types of data sources.
   *
   * This method overloaded parent method of select element, to allow
   * loading options with fancy attributes.
   *
   * @param mixed &$options Options source currently supports assoc array or DB_result
   * @param mixed $param1
   *   (optional) See function detail.
   * @param mixed $param2
   *   (optional) See function detail.
   * @param mixed $param3
   *   (optional) See function detail.
   * @param mixed $param4
   *   (optional) See function detail.
   *
   * @since      version 1.5.0 (2009-02-15)
   * @return PEAR_Error|NULL on error and TRUE on success
   * @throws     PEAR_Error
   * @see        loadArray()
   */
  public function load(
    &$options, $param1 = NULL, $param2 = NULL,
    $param3 = NULL, $param4 = NULL
  ) {
    switch (TRUE) {
      case ($options instanceof Iterator):
        $arr = [];
        foreach ($options as $key => $val) {
          $arr[$key] = $val;
        }
        return $this->loadArray($arr, $param1);

      default:
        return parent::load($options, $param1, $param2, $param3, $param4);
    }
  }

}
