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
 */

/**
 * This class holds functionality shared between various front end forms.
 */
trait CRM_Financial_Form_FrontEndPaymentFormTrait {

  /**
   * Alter line items for template.
   *
   * This is an early cut of what will ideally eventually be a hooklike call to the
   * CRM_Invoicing_Utils class with a potential end goal of moving this handling to an extension.
   *
   * @param $tplLineItems
   *
   * @return array
   */
  protected function alterLineItemsForTemplate($tplLineItems) {
    $getTaxDetails = FALSE;
    foreach ($tplLineItems as $key => $value) {
      foreach ($value as $k => $v) {
        if (isset($v['tax_rate'])) {
          if ($v['tax_rate'] != '') {
            $getTaxDetails = TRUE;
            // Cast to float to display without trailing zero decimals
            $tplLineItems[$key][$k]['tax_rate'] = (float) $v['tax_rate'];
          }
        }
      }
    }
    // @todo fix this to only return $tplLineItems. Calling function can check for tax rate and
    // do all invoicing related assigns
    // another discrete function (it's just one more iteration through a an array with only a handful of
    // lines so the separation of concerns is more important than 'efficiency'
    return [$getTaxDetails, $tplLineItems];
  }

}
