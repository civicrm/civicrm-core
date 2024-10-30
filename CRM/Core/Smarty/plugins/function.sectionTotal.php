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
 * CiviCRM's Smarty report section totals plugin
 *
 * Prints the correct report section total based on the given key and order in the section hierarchy
 *
 * @package CRM
 * @author Allen Shaw <allen@nswebsolutions.com>
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
 *
 * @return string
 *   the string, translated by gettext
 *
 * @deprecated This is called from table.tpl but we aim to remove
 * from there.
 */
function smarty_function_sectionTotal(array $params) {
  /* section totals are stored in template variable 'sectionTotals',
   * which is a two-dimensional array keyed to a string which is a delimited
   * concatenation (using CRM_Core_DAO::VALUE_SEPARATOR) of ordered permutations
   * of section header values, e.g.,
   * 'foo' => 10,
   * 'foo[VALUE_SEAPARATOR]bar' => 5,
   * 'foo[VALUE_SEAPARATOR]bar2' => 5
   * Note: This array is created and assigned to the template in CRM_Report_Form::sectionTotals()
   */

  static $sectionValues = [];

  // move back in the stack, if necessary
  if (count($sectionValues) > $params['depth']) {
    $sectionValues = array_slice($sectionValues, 0, $params['depth']);
  }

  // append the current value
  $sectionValues[] = $params['key'];

  // concatenate with pipes to build the right key
  $totalsKey = implode(CRM_Core_DAO::VALUE_SEPARATOR, $sectionValues);

  // return the corresponding total
  return $params['totals'][$totalsKey];
}
