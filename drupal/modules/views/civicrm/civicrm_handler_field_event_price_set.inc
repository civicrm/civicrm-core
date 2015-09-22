<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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


/*
 * Copyright (C) 2009 James Rakich
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 *
 * civicrm_handler_field_event_price_set.inc
 * Gathers Price Set Information for the event to be displayed in a list (inline, numbered or unnumbered)
 *
 */
class civicrm_handler_field_event_price_set extends views_handler_field_prerender_list {
  function construct() {
    parent::construct();
    $this->additional_fields['event_page_id'] = array('table' => 'civicrm_event_page', 'field' => 'id');
  }

  function query() {
    $this->add_additional_fields();
    $this->field_alias = $this->aliases['event_page_id'];
  }

  function pre_render($values) {

    if (!civicrm_initialize()) {
      return;
    }
    require_once 'CRM/Utils/Money.php';
    require_once 'CRM/Price/BAO/Set.php';
    require_once 'CRM/Core/OptionGroup.php';
    require_once 'CRM/Core/BAO/Discount.php';

    $eventPageIDs = array();
    $this->items = array();

    foreach ($values as $result) {
      $eventPageIDs[] = $result->{$this->aliases['event_page_id']};
    }


    if ($eventPageIDs) {
      foreach ($eventPageIDs as $eventPageID) {
        $priceSet = array();


        if (isset($eventPageID)) {
          if (!CRM_Price_BAO_PriceSet::getFor('civicrm_event_page', $eventPageID)) {
            //retrieve event fees.
            $discountId = CRM_Core_BAO_Discount::findSet($eventPageID, 'civicrm_event');
            if ($discountId) {
              CRM_Core_OptionGroup::getAssoc(
                CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_Discount',
                  $discountId,
                  'option_group_id'
                ),
                $priceSet, 'id'
              );
            }
            else {
              CRM_Core_OptionGroup::getAssoc("civicrm_event_page.amount.{$eventPageID}", $priceSet);
            }
          }
        }


        for ($i = 1; $i <= count($priceSet[label]); $i++) {
          $this->items[$eventPageID][] = $priceSet[label][$i] . " " . CRM_Utils_Money::format($priceSet[value][$i]);
        }
      }
    }
  }
}

