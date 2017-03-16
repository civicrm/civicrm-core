<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.7                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This form is intended to replace the overloading of many forms to generate a price set display.
 */
class CRM_Price_Form_PriceSetDisplay extends CRM_Core_Form {

  /**
   * PreProcess function.
   */
  public function preProcess() {
    $this->context = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'Standalone');
    $this->extends = CRM_Utils_Request::retrieve('extends', 'String', $this);
    $this->priceSetId = CRM_Utils_Request::retrieve('price_set_id', 'String', $this);
    $defaultPriceSetID = CRM_Price_BAO_PriceSet::getDefaultPriceSetID(strtolower($this->extends));
    if (empty($this->priceSetId)) {
      $this->priceSetId = $defaultPriceSetID;
    }
    $this->assign('defaultPriceSetID', $defaultPriceSetID);
    $this->assign('context', $this->context);
    $this->assign('extends', $this->extends);
    $this->assign('suppressForm', TRUE);
    $this->controller->_generateQFKey = FALSE;
  }

  /**
   * Set defaults.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = array();
    return $defaults;
  }

  /**
   * Build quick form.
   */
  public function buildQuickForm() {
    $this->set('priceSetId', $this->priceSetId);
    CRM_Price_BAO_PriceSet::buildPriceSet($this);
  }

}
