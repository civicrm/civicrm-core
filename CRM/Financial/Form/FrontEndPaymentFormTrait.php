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
   */
  protected function alterLineItemsForTemplate(&$tplLineItems) {
    if (!CRM_Invoicing_Utils::isInvoicingEnabled()) {
      return;
    }
    // @todo this should really be the first time we are determining
    // the tax rates - we can calculate them from the financial_type_id
    // & amount here so we didn't need a deeper function to semi-get
    // them but not be able to 'format them right' because they are
    // potentially being used for 'something else'.
    // @todo invoicing code - please feel the hate. Also move this 'hook-like-bit'
    // to the CRM_Invoicing_Utils class.
    foreach ($tplLineItems as $key => $value) {
      foreach ($value as $k => $v) {
        if (isset($v['tax_rate']) && $v['tax_rate'] != '') {
          // These only need assigning once, but code is more readable with them here
          $this->assign('getTaxDetails', TRUE);
          $this->assign('taxTerm', CRM_Invoicing_Utils::getTaxTerm());
          // Cast to float to display without trailing zero decimals
          $tplLineItems[$key][$k]['tax_rate'] = (float) $v['tax_rate'];
        }
      }
    }
  }

  /**
   * Assign line items to the template.
   *
   * @param $tplLineItems
   */
  protected function assignLineItemsToTemplate($tplLineItems) {
    // @todo this should be a hook that invoicing code hooks into rather than a call to it.
    $this->alterLineItemsForTemplate($tplLineItems);
    $this->assign('lineItem', $tplLineItems);
  }

}
