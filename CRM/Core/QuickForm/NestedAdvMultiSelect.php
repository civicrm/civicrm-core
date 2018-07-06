<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright U.S. PIRG Education Fund (c) 2007                        |
 | Licensed to CiviCRM under the Academic Free License version 3.0.   |
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
 * @copyright U.S. PIRG Education Fund 2007
 * $Id$
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
        $arr = array();
        foreach ($options as $key => $val) {
          $arr[$key] = $val;
        }
        return $this->loadArray($arr, $param1);

      default:
        return parent::load($options, $param1, $param2, $param3, $param4);
    }
  }

}
