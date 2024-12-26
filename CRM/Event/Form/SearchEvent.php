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
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
    $defaults = [];
    $defaults['eventsByDates'] = 0;

    $showHide = new CRM_Core_ShowHideBlocks();
    if (empty($defaults['eventsByDates'])) {
      $showHide->addHide('id_fromToDates');
    }

    $showHide->addToTemplate();
    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    $this->add('text', 'title', ts('Event Name'),
      CRM_Core_DAO::getAttribute('CRM_Event_DAO_Event', 'title')
    );

    $this->addSelect('event_type_id', ['multiple' => TRUE, 'context' => 'search']);

    $searchOption = [ts('Show Current and Upcoming Events'), ts('Search All or by Date Range')];
    $this->addRadio('eventsByDates', ts('Events by Dates'), $searchOption, ['onclick' => "return showHideByValue('eventsByDates','1','id_fromToDates','block','radio',true);"], '&nbsp;');

    $this->add('datepicker', 'start_date', ts('From'), [], FALSE, ['time' => FALSE]);
    $this->add('datepicker', 'end_date', ts('To'), [], FALSE, ['time' => FALSE]);

    CRM_Campaign_BAO_Campaign::addCampaignInComponentSearch($this);

    $this->addButtons([
      [
        'type' => 'refresh',
        'name' => ts('Search'),
        'isDefault' => TRUE,
      ],
    ]);
  }

  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $parent = $this->controller->getParent();
    $parent->set('searchResult', 1);
    if (!empty($params)) {
      $fields = ['title', 'event_type_id', 'start_date', 'end_date', 'eventsByDates', 'campaign_id'];
      foreach ($fields as $field) {
        if (isset($params[$field]) &&
          !CRM_Utils_System::isNull($params[$field])
        ) {
          if ($field === 'end_date') {
            $parent->set($field, $params[$field] . ' 23:59:59');
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
