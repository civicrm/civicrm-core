<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

require_once 'CRM/Core/Form.php';
require_once 'CRM/Core/OptionGroup.php';
class CRM_Auction_Form_SearchAuction extends CRM_Core_Form {
  function setDefaultValues() {
    $defaults = array();
    $defaults['auctionsByDates'] = 0;

    require_once 'CRM/Core/ShowHideBlocks.php';
    $this->_showHide = new CRM_Core_ShowHideBlocks();
    if (empty($defaults['auctionsByDates'])) {
      $this->_showHide->addHide('id_fromToDates');
    }

    $this->_showHide->addToTemplate();
    return $defaults;
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->add('text', 'title', ts('Find'),
      array(CRM_Core_DAO::getAttribute('CRM_Auction_DAO_Auction', 'title'))
    );

    $auctionsByDates = array();
    $searchOption = array(ts('Show Current and Upcoming Auctions'), ts('Search All or by Date Range'));
    $this->addRadio('auctionsByDates', ts('Auctions by Dates'), $searchOption, array('onclick' => "return showHideByValue('auctionsByDates','1','id_fromToDates','block','radio',true);"), "<br />");

    $this->add('date', 'start_date', ts('From'), CRM_Core_SelectValues::date('relative'));
    $this->addRule('start_date', ts('Select a valid Auction FROM date.'), 'qfDate');

    $this->add('date', 'end_date', ts('To'), CRM_Core_SelectValues::date('relative'));
    $this->addRule('end_date', ts('Select a valid Auction TO date.'), 'qfDate');

    $this->addButtons(array(
        array('type' => 'refresh',
          'name' => ts('Search'),
          'isDefault' => TRUE,
        ),
      ));
  }

  function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $parent = $this->controller->getParent();
    $parent->set('searchResult', 1);
    if (!empty($params)) {
      $fields = array('title', 'event_type_id', 'start_date', 'end_date', 'auctionsByDates');
      foreach ($fields as $field) {
        if (isset($params[$field]) &&
          !CRM_Utils_System::isNull($params[$field])
        ) {
          $parent->set($field, $params[$field]);
        }
        else {
          $parent->set($field, NULL);
        }
      }
    }
  }
}

