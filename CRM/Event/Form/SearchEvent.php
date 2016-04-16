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
 * $Id$
 *
 */
class CRM_Event_Form_SearchEvent extends CRM_Core_Form {

  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'Event';
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $defaults = array();
    $defaults['eventsByDates'] = 0;

    $this->_showHide = new CRM_Core_ShowHideBlocks();
    if (empty($defaults['eventsByDates'])) {
      $this->_showHide->addHide('id_fromToDates');
    }

    $this->_showHide->addToTemplate();
    return $defaults;
  }

  /**
   * Build the form object.
   *
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->add('text', 'title', ts('Find'),
      array(CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event', 'title'))
    );

    $this->addSelect('event_type_id', array('multiple' => TRUE, 'context' => 'search'));

    $eventsByDates = array();
    $searchOption = array(ts('Show Current and Upcoming Events'), ts('Search All or by Date Range'));
    $this->addRadio('eventsByDates', ts('Events by Dates'), $searchOption, array('onclick' => "return showHideByValue('eventsByDates','1','id_fromToDates','block','radio',true);"), "<br />");

    $this->addDate('start_date', ts('From'), FALSE, array('formatType' => 'searchDate'));
    $this->addDate('end_date', ts('To'), FALSE, array('formatType' => 'searchDate'));

    CRM_Campaign_BAO_Campaign::addCampaignInComponentSearch($this);

    $this->addButtons(array(
      array(
        'type' => 'refresh',
        'name' => ts('Search'),
        'isDefault' => TRUE,
      ),
    ));
  }

  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $parent = $this->controller->getParent();
    $parent->set('searchResult', 1);
    if (!empty($params)) {
      $fields = array('title', 'event_type_id', 'start_date', 'end_date', 'eventsByDates', 'campaign_id');
      foreach ($fields as $field) {
        if (isset($params[$field]) &&
          !CRM_Utils_System::isNull($params[$field])
        ) {
          if (substr($field, -4) == 'date') {
            $time = ($field == 'end_date') ? '235959' : NULL;
            $parent->set($field, CRM_Utils_Date::processDate($params[$field], $time));
          }
          else {
            $parent->set($field, $params[$field]);
          }
        }
        else {
          $parent->set($field, NULL);
        }
      }
    }
  }

}
