<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * CiviCRM's Smarty report section totals plugin
 *
 * Prints the correct report section total based on the given key and order in the section hierarchy
 *
 * @package CRM
 * @author Allen Shaw <allen@nswebsolutions.com>
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 */

/**
 * Smarty block function for printing the correct report section total
 *
 * Smarty param:  string $key     value of the current section column
 * Smarty param:  int    $depth   the depth of the current section
 *                                (sections declared first have lesser depth, starting at 0)
 *
 * @param array $params
 *   Template call's parameters.
 * @param CRM_Core_Smarty $smarty
 *   The Smarty object.
 *
 * @return string
 *   the string, translated by gettext
 */
function smarty_function_sectionTotal($params, &$smarty) {
  /* section totals are stored in template variable 'sectionTotals',
   * which is a two-dimensional array keyed to a string which is a delimited
   * concatenation (using CRM_Core_DAO::VALUE_SEPARATOR) of ordered permutations
   * of section header values, e.g.,
   * 'foo' => 10,
   * 'foo[VALUE_SEAPARATOR]bar' => 5,
   * 'foo[VALUE_SEAPARATOR]bar2' => 5
   * Note: This array is created and assigned to the template in CRM_Report_Form::sectionTotals()
   */

  static $sectionValues = array();

  // move back in the stack, if necessary
  if (count($sectionValues) > $params['depth']) {
    $sectionValues = array_slice($sectionValues, 0, $params['depth']);
  }

  // append the current value
  $sectionValues[] = $params['key'];

  // concatenate with pipes to build the right key
  $totalsKey = implode(CRM_Core_DAO::VALUE_SEPARATOR, $sectionValues);

  // return the corresponding total
  return $smarty->_tpl_vars['sectionTotals'][$totalsKey];
}
